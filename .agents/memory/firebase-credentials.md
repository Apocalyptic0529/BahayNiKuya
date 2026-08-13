---
name: Firebase credential input
description: How Firebase service-account credentials are supplied to this Replit PHP app.
---

The Firebase service-account secret may be supplied as complete JSON or as a single-line value prefixed with `base64:` followed by the Base64-encoded JSON.

**Why:** Multiline service-account JSON can be difficult to enter through the secure secret form, while the one-line representation is accepted reliably without exposing the credential in chat or source control.

**How to apply:** Keep the service-account value in Replit Secrets only. If the input UI rejects multiline JSON, encode the downloaded JSON locally and save the resulting `base64:` value as `FIREBASE_SERVICE_ACCOUNT_JSON`.