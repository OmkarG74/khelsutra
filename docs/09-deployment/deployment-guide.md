# Production Deployment Guide

## 1. Containerization
Build and tag the production backend Docker container:
```bash
docker build -t khelsutra-backend:latest -f deployment/Dockerfile .
```

## 2. Database Migration on AWS RDS
Ensure MySQL 8.0+ is initialized on Amazon Aurora/RDS:
```bash
mysql -h <rds-endpoint> -u <admin-user> -p khelsutra < database/sports_management_database.sql
mysql -h <rds-endpoint> -u <admin-user> -p khelsutra < database/seeds/roles.sql
mysql -h <rds-endpoint> -u <admin-user> -p khelsutra < database/seeds/permissions.sql
mysql -h <rds-endpoint> -u <admin-user> -p khelsutra < database/seeds/reference-data.sql
```

## 3. ECS Service Deployment
- Register new Task Definition revision pointing to updated ECR image tag.
- Update ECS Service using Rolling Deployment strategy (min healthy percent: 100%, max percent: 200%).
- Verify health check on `/api/v1/health`.
