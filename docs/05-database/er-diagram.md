# Entity Relationship Diagram — KhelSutra

Please refer to the primary visual diagram at [`database/diagrams/er-diagram.mermaid`](file:///database/diagrams/er-diagram.mermaid) and domain explanations at [`database/diagrams/er-diagram.md`](file:///database/diagrams/er-diagram.md).

```mermaid
erDiagram
    ORGANIZATIONS ||--o{ USERS : "associates"
    ORGANIZATIONS ||--o{ ATHLETES : "owns"
    ORGANIZATIONS ||--o{ EMPLOYEES : "employs"
    ORGANIZATIONS ||--o{ TEAMS : "manages"
    ORGANIZATIONS ||--o{ VENUES : "maintains"
    ORGANIZATIONS ||--o{ TOURNAMENTS : "hosts"
    
    ATHLETES ||--o{ TEAM_MEMBERS : "enrolled_in"
    TEAMS ||--o{ TEAM_MEMBERS : "composed_of"
    EMPLOYEES ||--o{ TEAM_COACHES : "coaches"
    TEAMS ||--o{ TEAM_COACHES : "guided_by"

    VENUES ||--o{ VENUE_FACILITIES : "contains"
    VENUE_FACILITIES ||--o{ VENUE_BOOKINGS : "booked_in"

    TOURNAMENTS ||--o{ FIXTURES : "schedules"
    FIXTURES ||--o| MATCHES : "records"
```
