# Master Test Cases Catalog

| Test Case ID | Module | Title | Precondition | Test Steps | Expected Result | Status |
|---|---|---|---|---|---|---|
| TC-AUTH-01 | Auth | Successful Login | User active in DB | POST /api/v1/auth/login with valid email/pwd | 200 OK with Bearer token & role payload | Active |
| TC-AUTH-02 | Auth | Invalid Password | User exists | POST /api/v1/auth/login with invalid pwd | 401 Unauthorized with standard error envelope | Active |
| TC-TNT-01 | Tenancy | Tenant Isolation | Athlete in Org A | User of Org B requests athlete from Org A | 404 Not Found (scoped query prevents leakage) | Active |
| TC-RBAC-01 | RBAC | Permission Gate | Athlete role | Athlete attempts to create Tournament | 403 Forbidden | Active |
| TC-VEN-01 | Venue | Booking Overlap | Slot 10:00-11:00 booked | Attempt booking 10:30-11:30 for same facility | 409 Conflict with overlap error | Active |
| TC-HLT-01 | System | Health Check | MySQL active | GET /api/v1/health | 200 OK with database: "connected" | Active |
