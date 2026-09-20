# Branching Strategy & Workflow

## Git Flow Standard
```
main (Production releases)
  ▲
  │ (Release PR / Hotfix PR)
develop (Default active development integration)
  ▲
  │ (Feature PRs)
feature/<module-name>-<description>
```

### Branches
1. `main`: Strictly reserved for verified, production-ready code. Tagged with SemVer releases (`v1.0.0`).
2. `develop`: Aggregation branch for team integration. Continuous Integration triggers on every push.
3. `feature/*`: Short-lived developer branches branching off and merging back into `develop`.
4. `hotfix/*`: Emergency fixes cut from `main` and backported to `develop`.

### Team Member Allocation
- Team Member 1: `feature/core-auth-rbac-tenancy`
- Team Member 2: `feature/athletes-coaches-teams`
- Team Member 3: `feature/tournaments-venues-events`
- Team Member 4: `feature/hr-payroll-inventory-finance`
- Team Member 5: `feature/flutter-mobile-client`
