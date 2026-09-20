# AWS Deployment Architecture — KhelSutra

## Cloud Architecture Blueprint

```
                     Internet / Mobile / Web Clients
                                   │
                                   ▼
                       [Route 53 DNS Service]
                                   │
                                   ▼
                   [AWS CloudFront CDN + AWS WAF]
                                   │
                                   ▼
               [Application Load Balancer (ALB)] (TLS Termination)
                                   │
                    ┌──────────────┴──────────────┐
                    ▼                             ▼
         [ECS Fargate Task - PHP]      [ECS Fargate Task - PHP]
            (Private Subnet A)            (Private Subnet B)
                    │                             │
                    └──────────────┬──────────────┘
                                   │
             ┌─────────────────────┼─────────────────────┐
             ▼                                           ▼
[Amazon Aurora MySQL Multi-AZ]                [Amazon S3 Private Bucket]
- Primary Writer (AZ-1)                       - Athlete Photos & Documents
- Read Replica (AZ-2)                         - Medical Reports
- Auto-scaling connections                    - Signed URL access only
```

## Security & Isolation
- **VPC Subnets**: Backend containers run strictly in private subnets with NAT Gateways for outbound access.
- **Database Subnet Group**: Isolated from direct public internet access; accessible exclusively from ECS security group on port 3306.
- **Secrets**: Database credentials and APP_KEY managed securely via AWS Systems Manager Parameter Store / AWS Secrets Manager.
