# Database – Tshijuka RDP

## Single schema file

- **`document.sql`** – Full schema: all tables (including CountryAgents and PaymentAgentFlow for cross-border payments) and seed data.  
  Use for new installs. **Warning:** it may drop or assume an existing database.

## Forgot password & OTP

- Forgot password uses **OTP by email** (no link). Table: **`PasswordResetOtp`**.
- Flow: User enters email → receives 6-digit code → enters code + new password on `reset-password-verify.php` → password updated.

## Document image stored in database

- The `Document` table includes `imageData` (LONGBLOB) and `imageMime` (VARCHAR) so document images can be stored in the DB and viewed reliably. For an **existing** database that already had `Document` without these columns, run the optional ALTER shown at the **end of document.sql** (commented section) once.

## Tables

| Table               | Purpose |
|---------------------|--------|
| User                | Users (Seeker, Issuer, Admin, Admissions Office) |
| Subscribe           | Issuer/Admissions subscription info |
| DocumentType / Status | Lookups (types: Identity, Educational, History, Contract) |
| Document            | Document requests |
| PrelossDocuments    | Pre-loss file storage |
| TshijukaPackHistory | Pack sharing history |
| Chat                | Chat messages (seeker–issuer + admissions) |
| UserMfa             | MFA (email OTP) for login |
| PasswordResetOtp    | Forgot-password OTP codes |
| PaystackPayments    | Payment records |
| CountryAgents       | Agents per country (Momo + bank details for compensation) |
| PaymentAgentFlow    | Payment → agent assignment and status (agent_notified → agent_paid_momo → compensation_sent) |
