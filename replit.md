# Bahay ni Kuya

## Overview

Bahay ni Kuya is a PHP real-estate listing application with buyer, seller, and admin dashboards. The app stores users, listings, inquiries, favorites, reports, seller applications, and newsletter subscribers in Cloud Firestore through the Firebase REST API.

## Running on Replit

The app runs with PHP's built-in web server on port 5000. The shared environment must contain:

- `FIREBASE_PROJECT_ID` — the Firebase project ID
- `FIREBASE_SERVICE_ACCOUNT_JSON` — the complete service-account JSON, or a single-line value prefixed with `base64:` containing that JSON encoded as Base64

Cloud Firestore must be enabled in the Firebase project, and the service account must have permission to read and write Firestore documents.

## Data model

Firestore collection names match the legacy SQL table names: `users`, `properties`, `inquiries`, `seller_applications`, `favorites`, `reports`, `newsletter_subscribers`, and `contact_messages`. Numeric IDs are preserved as document fields and document IDs so the existing PHP pages and APIs can continue to use integer IDs.
