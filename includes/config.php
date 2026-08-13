<?php
/**
 * Firebase / Cloud Firestore configuration.
 *
 * The legacy application uses a mysqli-like $conn object throughout its
 * pages. FirebaseConnection keeps that small interface intact while storing
 * every collection in Cloud Firestore through its REST API.
 */

define('SITE_NAME', 'Bahay ni Kuya');
define('BASE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/');

$isLocalEnvironment = false;

class FirebaseResult {
    private array $rows;
    private int $index = 0;
    public int $num_rows;

    public function __construct(array $rows = []) {
        $this->rows = array_values($rows);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc(): ?array {
        return $this->index < $this->num_rows ? $this->rows[$this->index++] : null;
    }
}

class FirebaseStatement {
    private FirebaseConnection $connection;
    private string $query;
    private array $params = [];

    public function __construct(FirebaseConnection $connection, string $query) {
        $this->connection = $connection;
        $this->query = $query;
    }

    public function bind_param(string $types, &...$params): bool {
        $this->params = [];
        foreach ($params as &$param) {
            $this->params[] = $param;
        }
        return true;
    }

    public function execute(): bool {
        return $this->connection->executePrepared($this->query, $this->params);
    }

    public function get_result(): FirebaseResult {
        return $this->connection->queryPrepared($this->query, $this->params);
    }

    public function close(): bool {
        return true;
    }
}

class FirebaseConnection {
    public ?string $connect_error = null;
    public string $error = '';
    public int $insert_id = 0;
    public int $affected_rows = 0;

    private string $projectId;
    private array $serviceAccount = [];
    private ?string $accessToken = null;
    private int $tokenExpiresAt = 0;

    public function __construct() {
        $this->projectId = trim((string) getenv('FIREBASE_PROJECT_ID'));
        $credentials = (string) getenv('FIREBASE_SERVICE_ACCOUNT_JSON');

        if ($this->projectId === '' || $credentials === '') {
            $this->fail('FIREBASE_PROJECT_ID and FIREBASE_SERVICE_ACCOUNT_JSON must be configured.');
            return;
        }

        // The secure secret field can accept a single line more reliably than
        // multiline JSON. Accept "base64:<encoded-json>" as an alternative.
        if (str_starts_with(trim($credentials), 'base64:')) {
            $decodedCredentials = base64_decode(substr(trim($credentials), 7), true);
            if ($decodedCredentials === false) {
                $this->fail('FIREBASE_SERVICE_ACCOUNT_JSON contains invalid Base64 data.');
                return;
            }
            $credentials = $decodedCredentials;
        }

        $decoded = json_decode($credentials, true);
        if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
            $this->fail('FIREBASE_SERVICE_ACCOUNT_JSON is not valid service-account JSON.');
        } else {
            $this->serviceAccount = $decoded;
        }
    }

    private function fail(string $message): void {
        $this->connect_error = $message;
        $this->error = $message;
    }

    public function real_escape_string($value): string {
        return addslashes((string) $value);
    }

    public function set_charset($charset): bool {
        return true;
    }

    public function begin_transaction(): bool {
        return true;
    }

    public function commit(): bool {
        return true;
    }

    public function rollback(): bool {
        return true;
    }

    public function prepare($query): FirebaseStatement {
        return new FirebaseStatement($this, (string) $query);
    }

    public function query($query): FirebaseResult|bool {
        $this->error = '';
        try {
            $trimmed = trim((string) $query);
            if (stripos($trimmed, 'SELECT') === 0) {
                return $this->runSelect($trimmed, []);
            }
            return $this->executePrepared($trimmed, []);
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();
            return false;
        }
    }

    public function queryPrepared(string $query, array $params): FirebaseResult {
        try {
            return $this->runSelect(trim($query), $params);
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();
            return new FirebaseResult();
        }
    }

    public function executePrepared(string $query, array $params): bool {
        $this->error = '';
        try {
            $sql = trim($query);
            if (stripos($sql, 'INSERT INTO') === 0) {
                return $this->runInsert($sql, $params);
            }
            if (stripos($sql, 'UPDATE ') === 0) {
                return $this->runUpdate($sql, $params);
            }
            if (stripos($sql, 'DELETE FROM') === 0) {
                return $this->runDelete($sql, $params);
            }
            $this->error = 'Unsupported Firestore query: ' . $sql;
            return false;
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();
            return false;
        }
    }

    private function runInsert(string $sql, array $params): bool {
        if (!preg_match('/INSERT\s+INTO\s+([a-z_]+)\s*\((.*?)\)\s*VALUES\s*\((.*?)\)/is', $sql, $match)) {
            throw new RuntimeException('Could not parse insert query.');
        }
        $collection = strtolower($match[1]);
        $columns = array_map('trim', explode(',', $match[2]));
        $values = $this->resolveValues($match[3], $params);
        $document = [];
        foreach ($columns as $index => $column) {
            $document[$column] = $values[$index] ?? null;
        }
        $document['created_at'] = $document['created_at'] ?? $this->now();
        $document['updated_at'] = $document['updated_at'] ?? $document['created_at'];
        $id = $this->nextId($collection);
        $document['id'] = $id;
        $this->writeDocument($collection, (string) $id, $document);
        $this->insert_id = $id;
        $this->affected_rows = 1;
        return true;
    }

    private function runUpdate(string $sql, array $params): bool {
        if (!preg_match('/UPDATE\s+([a-z_]+)\s+SET\s+(.*?)\s+WHERE\s+(.+)$/is', $sql, $match)) {
            throw new RuntimeException('Could not parse update query.');
        }
        $collection = strtolower($match[1]);
        $assignments = $this->parseAssignments($match[2]);
        $documents = $this->getCollection($collection);
        $this->affected_rows = 0;
        foreach ($documents as $document) {
            if (!$this->matches($document, $match[3], $params, $assignments['used'])) {
                continue;
            }
            $offset = $assignments['used'];
            foreach ($assignments['columns'] as $column) {
                $document[$column] = $this->resolveValue($assignments['values'][$column], $params, $offset);
            }
            $document['updated_at'] = $this->now();
            $this->writeDocument($collection, (string) $document['id'], $document);
            $this->affected_rows++;
        }
        return true;
    }

    private function runDelete(string $sql, array $params): bool {
        if (!preg_match('/DELETE\s+FROM\s+([a-z_]+)(?:\s+WHERE\s+(.+))?$/is', $sql, $match)) {
            throw new RuntimeException('Could not parse delete query.');
        }
        $collection = strtolower($match[1]);
        $documents = $this->getCollection($collection);
        $this->affected_rows = 0;
        foreach ($documents as $document) {
            if (!empty($match[2]) && !$this->matches($document, $match[2], $params, 0)) {
                continue;
            }
            $this->deleteDocument($collection, (string) $document['id']);
            $this->affected_rows++;
        }
        return true;
    }

    private function runSelect(string $sql, array $params): FirebaseResult {
        $lower = strtolower($sql);
        if (strpos($lower, 'count(*)') !== false || strpos($lower, 'group by') !== false) {
            return $this->runAggregate($sql, $params);
        }

        if (!preg_match('/SELECT\s+(.*?)\s+FROM\s+([a-z_]+)(.*)$/is', $sql, $match)) {
            throw new RuntimeException('Could not parse select query.');
        }
        $fields = trim($match[1]);
        $collection = strtolower($match[2]);
        $tail = trim($match[3]);
        $rows = $this->getCollection($collection);
        $rows = $this->applyJoins($rows, $tail, $collection);

        if (preg_match('/WHERE\s+(.+?)(?:\s+ORDER\s+BY|\s+LIMIT|$)/is', $tail, $where)) {
            $rows = array_values(array_filter($rows, fn($row) => $this->matches($row, $where[1], $params, 0)));
        }

        if (stripos($tail, 'ORDER BY') !== false) {
            preg_match('/ORDER\s+BY\s+([a-z_.]+)(?:\s+(ASC|DESC))?/i', $tail, $order);
            $orderField = strtolower(str_replace(['p.', 'u.', 'i.', 'r.', 'f.', 'sa.'], '', $order[1] ?? 'created_at'));
            $direction = strtoupper($order[2] ?? 'ASC');
            usort($rows, function ($a, $b) use ($orderField, $direction) {
                $comparison = ($a[$orderField] ?? '') <=> ($b[$orderField] ?? '');
                return $direction === 'DESC' ? -$comparison : $comparison;
            });
        }
        if (preg_match('/LIMIT\s+(\d+)/i', $tail, $limit)) {
            $rows = array_slice($rows, 0, (int) $limit[1]);
        }

        if ($fields !== '*') {
            $selected = [];
            foreach ($rows as $row) {
                $out = [];
                foreach (explode(',', $fields) as $field) {
                    $parts = preg_split('/\s+as\s+/i', trim($field));
                    $sourceExpression = trim($parts[0]);
                    if ($sourceExpression === '*') {
                        $out = array_merge($out, $row);
                        continue;
                    }
                    if (str_ends_with($sourceExpression, '.*')) {
                        $prefix = substr($sourceExpression, 0, -2);
                        foreach ($row as $key => $value) {
                            if (str_starts_with($key, $prefix . '.')) {
                                $out[substr($key, strlen($prefix) + 1)] = $value;
                            }
                        }
                        continue;
                    }
                    $source = trim($sourceExpression);
                    $target = trim($parts[1] ?? $source);
                    $out[$target] = $row[$source] ?? $row[$this->fieldName($source)] ?? null;
                }
                $selected[] = $out;
            }
            $rows = $selected;
        }
        return new FirebaseResult($rows);
    }

    private function runAggregate(string $sql, array $params): FirebaseResult {
        preg_match('/FROM\s+([a-z_]+)/i', $sql, $collectionMatch);
        $collection = strtolower($collectionMatch[1] ?? '');
        $rows = $this->getCollection($collection);
        if (preg_match('/WHERE\s+(.+?)(?:\s+GROUP BY|$)/is', $sql, $where)) {
            $rows = array_values(array_filter($rows, fn($row) => $this->matches($row, $where[1], $params, 0)));
        }
        if (preg_match('/GROUP\s+BY\s+([a-z_.]+)/i', $sql, $group)) {
            $field = strtolower(str_replace(['p.', 'u.', 'i.'], '', $group[1]));
            $counts = [];
            foreach ($rows as $row) {
                $key = (string) ($row[$field] ?? '');
                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
            $result = [];
            foreach ($counts as $key => $count) {
                $result[] = [$field => $key, 'count' => $count];
            }
            return new FirebaseResult($result);
        }
        return new FirebaseResult([['count' => count($rows)]]);
    }

    private function parseAssignments(string $text): array {
        $columns = [];
        $values = [];
        $used = 0;
        foreach (explode(',', $text) as $assignment) {
            [$column, $value] = array_pad(explode('=', $assignment, 2), 2, '');
            $column = trim(str_replace(['p.', 'u.', 'i.', 'r.'], '', $column));
            $columns[] = $column;
            $values[$column] = trim($value);
            if (trim($value) === '?') $used++;
        }
        return compact('columns', 'values', 'used');
    }

    private function resolveValues(string $text, array $params): array {
        $values = [];
        $index = 0;
        foreach (explode(',', $text) as $value) {
            $values[] = $this->resolveValue(trim($value), $params, $index);
        }
        return $values;
    }

    private function resolveValue(string $value, array $params, int &$index) {
        if ($value === '?') return $params[$index++] ?? null;
        $value = trim($value, " \t\n\r\0\x0B'\"");
        if (strcasecmp($value, 'null') === 0) return null;
        if (strcasecmp($value, 'current_timestamp') === 0) return $this->now();
        if (is_numeric($value)) return str_contains($value, '.') ? (float) $value : (int) $value;
        return stripslashes($value);
    }

    private function matches(array $document, string $condition, array $params, int $startIndex): bool {
        $condition = trim($condition);
        $condition = preg_replace('/\s+/', ' ', $condition);
        $parts = preg_split('/\s+AND\s+/i', $condition);
        $paramIndex = $startIndex;
        foreach ($parts as $part) {
            $part = trim($part, " ()");
            if (preg_match('/^(1|true)\s*=\s*(1|true)$/i', $part)) {
                continue;
            }
            if (preg_match('/\s+OR\s+/i', $part)) {
                $orParts = preg_split('/\s+OR\s+/i', $part);
                $matched = false;
                foreach ($orParts as $orPart) {
                    if ($this->matches($document, $orPart, $params, $paramIndex)) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) return false;
                continue;
            }
            if (preg_match('/^\((.+)\)$/', $part, $nested)) $part = $nested[1];
            if (preg_match('/(.+?)\s+LIKE\s+(.+)/i', $part, $match)) {
                $field = $this->fieldName($match[1]);
                $value = $this->resolveValue(trim($match[2]), $params, $paramIndex);
                $needle = trim((string) $value, '%');
                if (stripos((string) ($document[$field] ?? ''), $needle) === false) return false;
                continue;
            }
            if (preg_match('/(.+?)\s*(=|>=|<=|<>|!=|>|<)\s*(.+)/', $part, $match)) {
                $field = $this->fieldName($match[1]);
                $actual = $document[$field] ?? null;
                $expected = $this->resolveValue(trim($match[3]), $params, $paramIndex);
                $operator = $match[2];
                $ok = match ($operator) {
                    '=' => (string) $actual === (string) $expected,
                    '!=' => (string) $actual !== (string) $expected,
                    '<>' => (string) $actual !== (string) $expected,
                    '>=' => $actual >= $expected,
                    '<=' => $actual <= $expected,
                    '>' => $actual > $expected,
                    '<' => $actual < $expected,
                    default => false
                };
                if (!$ok) return false;
            }
        }
        return true;
    }

    private function fieldName(string $field): string {
        return strtolower(trim(str_replace(['p.', 'u.', 'i.', 'r.', 'f.', 'sa.'], '', $field), " `()"));
    }

    private function applyJoins(array $rows, string $tail, string $baseCollection): array {
        $fromAlias = strtolower($baseCollection);
        if (preg_match('/^\s*([a-z_]+)\s+/i', $tail, $fromMatch)) {
            $fromAlias = strtolower($fromMatch[1]);
        }
        foreach ($rows as &$row) {
            foreach ($row as $key => $value) {
                $row[$fromAlias . '.' . $key] = $value;
            }
        }
        unset($row);

        preg_match_all(
            '/JOIN\s+([a-z_]+)\s+([a-z_]+)\s+ON\s+([a-z_]+)\.([a-z_]+)\s*=\s*([a-z_]+)\.([a-z_]+)/i',
            $tail,
            $joins,
            PREG_SET_ORDER
        );
        foreach ($joins as $join) {
            $joinCollection = strtolower($join[1]);
            $joinAlias = strtolower($join[2]);
            $leftAlias = strtolower($join[3]);
            $leftField = strtolower($join[4]);
            $rightAlias = strtolower($join[5]);
            $rightField = strtolower($join[6]);
            $joinedDocuments = $this->getCollection($joinCollection);
            $filtered = [];
            foreach ($rows as $row) {
                $leftValue = $row[$leftAlias . '.' . $leftField] ?? $row[$leftField] ?? null;
                $rightValue = $leftAlias === $rightAlias
                    ? ($row[$rightAlias . '.' . $rightField] ?? $row[$rightField] ?? null)
                    : null;
                if ($rightAlias === $joinAlias) {
                    $matches = array_values(array_filter($joinedDocuments, fn($document) =>
                        (string) ($document[$rightField] ?? '') === (string) $leftValue
                    ));
                } else {
                    $matches = array_values(array_filter($joinedDocuments, fn($document) =>
                        (string) ($document[$leftField] ?? '') === (string) $rightValue
                    ));
                }
                foreach ($matches as $document) {
                    $copy = $row;
                    foreach ($document as $key => $value) {
                        $copy[$joinAlias . '.' . $key] = $value;
                    }
                    $filtered[] = $copy;
                }
            }
            $rows = $filtered;
        }
        return $rows;
    }

    private function nextId(string $collection): int {
        $ids = array_map(fn($doc) => (int) ($doc['id'] ?? 0), $this->getCollection($collection));
        return $ids ? max($ids) + 1 : 1;
    }

    private function now(): string {
        return date('Y-m-d H:i:s');
    }

    private function getCollection(string $collection): array {
        $response = $this->request('GET', $this->baseUrl() . '/' . rawurlencode($collection));
        $documents = [];
        foreach (($response['documents'] ?? []) as $document) {
            $id = basename($document['name']);
            $row = $this->decodeFields($document['fields'] ?? []);
            $row['id'] = isset($row['id']) ? (int) $row['id'] : (int) $id;
            $documents[] = $row;
        }
        return $documents;
    }

    private function writeDocument(string $collection, string $id, array $document): void {
        $url = $this->baseUrl() . '/' . rawurlencode($collection) . '/' . rawurlencode($id);
        $this->request('PATCH', $url, ['fields' => $this->encodeFields($document)]);
    }

    private function deleteDocument(string $collection, string $id): void {
        $url = $this->baseUrl() . '/' . rawurlencode($collection) . '/' . rawurlencode($id);
        $this->request('DELETE', $url);
    }

    private function baseUrl(): string {
        return 'https://firestore.googleapis.com/v1/projects/' . rawurlencode($this->projectId) . '/databases/(default)/documents';
    }

    private function accessToken(): string {
        if (!$this->serviceAccount) {
            throw new RuntimeException($this->error ?: 'Firebase service-account credentials are not configured.');
        }
        if ($this->accessToken && time() < $this->tokenExpiresAt - 60) return $this->accessToken;
        $now = time();
        $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claim = $this->base64Url(json_encode([
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/datastore',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600
        ]));
        $unsigned = $header . '.' . $claim;
        if (!openssl_sign($unsigned, $signature, $this->serviceAccount['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Could not sign Firebase service-account token.');
        }
        $jwt = $unsigned . '.' . $this->base64Url($signature);
        $response = $this->httpRequest('POST', 'https://oauth2.googleapis.com/token', http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]), ['Content-Type: application/x-www-form-urlencoded']);
        if (empty($response['access_token'])) throw new RuntimeException('Firebase authentication failed.');
        $this->accessToken = $response['access_token'];
        $this->tokenExpiresAt = $now + (int) ($response['expires_in'] ?? 3600);
        return $this->accessToken;
    }

    private function request(string $method, string $url, ?array $body = null): array {
        $headers = ['Authorization: Bearer ' . $this->accessToken()];
        if ($body !== null) $headers[] = 'Content-Type: application/json';
        $response = $this->httpRequest($method, $url, $body === null ? null : json_encode($body), $headers);
        return is_array($response) ? $response : [];
    }

    private function httpRequest(string $method, string $url, ?string $body, array $headers = []): array {
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 20
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($raw === false || $error) throw new RuntimeException('Firebase request failed: ' . $error);
        $response = json_decode($raw, true);
        if ($status >= 400) {
            $message = $response['error']['message'] ?? ('HTTP ' . $status);
            throw new RuntimeException('Firebase error: ' . $message);
        }
        return is_array($response) ? $response : [];
    }

    private function encodeFields(array $fields): array {
        $encoded = [];
        foreach ($fields as $key => $value) {
            if ($value === null) $encoded[$key] = ['nullValue' => null];
            elseif (is_bool($value)) $encoded[$key] = ['booleanValue' => $value];
            elseif (is_int($value)) $encoded[$key] = ['integerValue' => (string) $value];
            elseif (is_float($value)) $encoded[$key] = ['doubleValue' => $value];
            elseif (is_array($value)) $encoded[$key] = ['arrayValue' => ['values' => array_values($this->encodeFields($value))]];
            else $encoded[$key] = ['stringValue' => (string) $value];
        }
        return $encoded;
    }

    private function decodeFields(array $fields): array {
        $decoded = [];
        foreach ($fields as $key => $field) {
            if (array_key_exists('nullValue', $field)) $decoded[$key] = null;
            elseif (isset($field['booleanValue'])) $decoded[$key] = $field['booleanValue'];
            elseif (isset($field['integerValue'])) $decoded[$key] = (int) $field['integerValue'];
            elseif (isset($field['doubleValue'])) $decoded[$key] = (float) $field['doubleValue'];
            elseif (isset($field['timestampValue'])) $decoded[$key] = str_replace('T', ' ', substr($field['timestampValue'], 0, 19));
            elseif (isset($field['stringValue'])) $decoded[$key] = $field['stringValue'];
            elseif (isset($field['arrayValue'])) $decoded[$key] = $this->decodeFields($field['arrayValue']['values'] ?? []);
            else $decoded[$key] = null;
        }
        return $decoded;
    }

    private function base64Url(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

$conn = new FirebaseConnection();
if ($conn->connect_error) {
    error_log($conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('Asia/Manila');
?>