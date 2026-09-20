# KhelSutra Database Layer

This directory houses the single source of truth for the **KhelSutra** Multi-Tenant Sports Management Database.

## Overview
The KhelSutra database architecture is a multi-tenant relational schema designed for MySQL 8.0+. Every organisation-owned entity is partitioned using foreign keys to `organizations(id)` and enforced at the backend through tenancy middleware and global query scopes.

## Directory Structure
- `sports_management_database.sql`: The primary schema DDL including tables, foreign keys, constraints, and indexes. Do NOT modify without architecture committee approval.
- `diagrams/`:
  - `er-diagram.md`: Domain-level entity relationships and conceptual overview.
  - `er-diagram.mermaid`: Visual Mermaid ER diagram depicting key table relationships.
- `seeds/`:
  - `roles.sql`: Standard system roles (`Super Admin`, `Sports Administrator`, `HR & Finance`, `Coach`, `Athlete`, `Venue & Tournament Manager`, `Inventory Manager`).
  - `permissions.sql`: Granular permissions categorized by module and action.
  - `reference-data.sql`: Core reference data including tournament formats, tournament levels, sports, and sport categories.

## Tenancy Rules
1. **Super Admin**: Provider-level user. Can access and manage all organisations.
2. **Organisation Users**: Scoped to an organisation via the `organization_users` pivot table.
3. **Data Isolation**: All organisation-owned tables (such as `athletes`, `teams`, `venues`, `departments`, `budgets`, etc.) must strictly reference `organization_id`. Backend queries must never leak data between tenants.

## Database Import
To import the schema into your local MySQL instance:
```bash
mysql -u root -p < sports_management_database.sql
mysql -u root -p khelsutra < seeds/roles.sql
mysql -u root -p khelsutra < seeds/permissions.sql
mysql -u root -p khelsutra < seeds/reference-data.sql
```
