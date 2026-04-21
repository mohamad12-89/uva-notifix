# AWS Account Checklist

Use this checklist in AWS Console before production cutover.

## Foundation

- [ ] IAM user has MFA enabled
- [ ] Root account has MFA enabled and is not used for daily work
- [ ] Billing alerts configured at `$5`, `$10`, `$20`
- [ ] Region standardized to `us-east-1`

## Cognito

- [ ] User pool exists: `us-east-1_y5iIepcok`
- [ ] App client exists (no secret): `6mobivpun3a7t3crihqs6vv1e7`
- [ ] Email verification required
- [ ] Groups created: `student`, `ta`, `professor`

## EC2 (Option A)

- [ ] Instance reachable by SSH
- [ ] Security group allows 22, 80, 443
- [ ] Elastic IP attached
- [ ] Nginx + PHP-FPM installed
- [ ] App deployed to `/var/www/notifix`

## Ops hardening

- [ ] CloudWatch alarms for CPU and status checks
- [ ] Daily DB backup cron configured
- [ ] GitHub Actions secrets configured for deploy workflow
