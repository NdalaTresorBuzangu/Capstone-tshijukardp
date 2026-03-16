# Tshijuka RDP – API (Admin / Integration only)

These endpoints are **not** linked on the public site. Only admins can see the "API & Integration" section in the admin dashboard. Use them for integration, partners, or to monetize document services.

## Base URL

- **Same origin:** `https://yoursite.com/api/` (replace with your domain)
- Send `Content-Type: application/json` for POST requests.
- Protected endpoints require a session: log in via `POST api/auth.php` first, then use the same session (cookie) for other calls.

---

## Endpoints

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `api/ping.php` | GET | None | Health check. Returns `success`, `message`, `timestamp`. |
| `api/auth.php` | POST | None | Programmatic login. Body: `{"email":"...","password":"..."}`. Returns user info and sets session. |
| `api/documents.php` | GET | Admin or Document Issuer | List documents. Admin: all; Issuer: own. Optional `?id=DOC-xxx` for one document. |
| `api/documents.php` | POST | Admin or Document Issuer | Create document request. Body: `userId`, `issuerId`, `typeId`, `description`, `location`. |
| `api/users.php` | GET | Admin only | List users. Optional `?role=Document Seeker` (or Document Issuer, Admin, Admissions Office) to filter. |

---

## Using the API to get money or services from requesters

1. **Charge for document requests**  
   Expose the Documents API to partners (schools, agencies). They send requests via API; you fulfill and charge per request or a subscription fee.

2. **White-label or integration**  
   Let third-party apps (mobile apps, other platforms) create document requests on behalf of their users. You receive the requests and get paid for processing.

3. **Bulk and reporting**  
   Use the Users and Documents APIs (with admin credentials) to pull data for billing, analytics, or reporting tools.

4. **Authentication**  
   Use `POST api/auth.php` with an admin or issuer account to get a session, then call other endpoints. Keep credentials secure and share only with trusted integrators.

---

## Example: create a document request (POST documents.php)

1. Log in:  
   `POST api/auth.php`  
   Body: `{"email":"issuer@example.com","password":"..."}`

2. Create request (use same session):  
   `POST api/documents.php`  
   Body:  
   `{"userId":1,"issuerId":2,"typeId":1,"description":"Transcript","location":"Kigali"}`

Response includes the new `documentID` and document details.
