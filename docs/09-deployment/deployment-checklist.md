# Deployment Checklist

Prior to production release:
- [ ] AWS RDS Aurora Multi-AZ instance provisioned and healthy.
- [ ] Production database migrated and baseline seeds loaded.
- [ ] SSL/TLS Certificate provisioned in AWS Certificate Manager (ACM).
- [ ] Application Load Balancer health check configured targeting `/api/v1/health`.
- [ ] Environment variables securely populated in AWS Secrets Manager.
- [ ] S3 bucket policy blocks public read; presigned URLs configured.
- [ ] CloudWatch Alarm set for 5xx error rate and DB CPU utilization > 75%.
- [ ] Post-deployment sanity test executed against live endpoints.
