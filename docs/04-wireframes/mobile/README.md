# Mobile Application Wireframes & Role-Aware Flow

## Architecture
- Mobile application is built with Flutter 3.x for native Android and iOS experiences.
- Navigation dynamically adapts based on the authenticated user's role.

## Flow:
```
[Splash Screen]
      │
      ▼
[Login Screen] (Token Authentication via /api/v1/auth/login)
      │
      ▼
[RoleRouter]
      ├── Coach Role   ──► [Coach Navigation Drawer / Bottom Nav]
      │                     ├── Dashboard
      │                     ├── Teams & Athletes
      │                     ├── Training & Attendance
      │                     ├── Fixtures & Matches
      │                     ├── Performance
      │                     └── Profile
      │
      └── Athlete Role ──► [Athlete Bottom Nav]
                            ├── Dashboard
                            ├── My Profile & My Team
                            ├── Training & Attendance
                            ├── Fixtures & Match Info
                            ├── Performance & Achievements
                            └── Leave Requests
```

*(Detailed mobile screen wireframes: PENDING DESIGN TEAM ASSETS)*
