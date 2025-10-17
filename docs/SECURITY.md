# Security Guide

This document outlines security best practices and improvements implemented in this project.

## Security Improvements Implemented

### 1. Command Injection Prevention

**Issue**: The `process.php` endpoint allowed arbitrary command execution, which is a critical security vulnerability.

**Fix**: The endpoint has been completely disabled and now returns a 403 error. Command execution via web interfaces should never be enabled in production environments.

**Recommendation**: If command execution is required for debugging, implement:
- Strong authentication and authorization
- Command whitelisting (never use blacklisting)
- Comprehensive audit logging
- Rate limiting
- IP address restrictions

### 2. Cross-Site Scripting (XSS) Protection

**Issue**: Multiple outputs in `index.php` did not escape user-controllable data, allowing potential XSS attacks.

**Fix**: All outputs now use `htmlspecialchars()` with proper flags:
```php
htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
```

**Protected outputs include**:
- Request URLs
- IP addresses
- Hostnames
- Environment variables
- HTTP headers
- API responses

### 3. Sensitive Information Disclosure

**Issue**: The application exposed sensitive information including:
- Access tokens in plain text
- Environment variables containing secrets
- Authentication headers
- Client secrets in source code

**Fix**: 
- Access tokens are no longer displayed
- Environment variables are filtered to hide sensitive values
- Sensitive HTTP headers are marked as `[FILTERED]`
- Client secrets must be stored in environment variables

**Sensitive patterns filtered**:
- SECRET
- PASSWORD
- KEY
- TOKEN
- CREDENTIAL
- CONNECTION

### 4. Hardcoded Secrets

**Issue**: Client ID and secret were hardcoded in the source code.

**Fix**: Credentials must now be provided via environment variables:
- `GRAPH_CLIENT_ID`
- `GRAPH_CLIENT_SECRET`
- `GRAPH_TENANT`

**Best Practices**:
- Use Azure Key Vault for secret storage
- Use Azure App Service Configuration settings
- Rotate secrets regularly
- Never commit secrets to source control

### 5. SSL/TLS Verification

**Issue**: cURL requests did not verify SSL certificates, allowing potential man-in-the-middle attacks.

**Fix**: All cURL requests now include:
```php
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```

### 6. Error Handling and Validation

**Issue**: No error handling or validation for external API calls.

**Fix**: 
- All cURL operations check for errors
- HTTP status codes are validated
- JSON parsing errors are handled
- Timeouts are configured
- Redirects are disabled for security

### 7. Security Headers

**Added to backend API**:
- `Content-Security-Policy`: Prevents XSS and code injection
- `X-Content-Type-Options`: Prevents MIME type sniffing
- `X-Frame-Options`: Prevents clickjacking
- `X-XSS-Protection`: Browser XSS protection
- `Referrer-Policy`: Prevents information leakage
- `Cache-Control`: Prevents caching of sensitive data

## Configuration

### Required Environment Variables

For the Graph API integration to work, set these environment variables:

```bash
# Azure App Service - Application Settings
az webapp config appsettings set --resource-group phpauth --name thx1140front --settings \
  GRAPH_CLIENT_ID="your-client-id" \
  GRAPH_CLIENT_SECRET="your-client-secret" \
  GRAPH_TENANT="your-tenant-id-or-domain"
```

### Azure Key Vault Integration (Recommended)

For production environments, use Azure Key Vault:

1. Create a Key Vault:
```bash
az keyvault create --name your-keyvault --resource-group phpauth --location "West Europe"
```

2. Store secrets:
```bash
az keyvault secret set --vault-name your-keyvault --name "GraphClientSecret" --value "your-secret"
```

3. Configure App Service to use Key Vault references:
```bash
az webapp config appsettings set --resource-group phpauth --name thx1140front --settings \
  GRAPH_CLIENT_SECRET="@Microsoft.KeyVault(VaultName=your-keyvault;SecretName=GraphClientSecret)"
```

## Security Checklist for Production

- [ ] Remove or secure the debug/diagnostic endpoints
- [ ] Enable HTTPS only (disable HTTP)
- [ ] Configure proper Content Security Policy
- [ ] Implement rate limiting
- [ ] Enable Azure App Service logging
- [ ] Configure Azure Monitor alerts
- [ ] Review and restrict CORS settings
- [ ] Implement proper session management
- [ ] Regular security audits and updates
- [ ] Use Azure Key Vault for all secrets
- [ ] Configure Azure AD Conditional Access
- [ ] Enable Multi-Factor Authentication (MFA)
- [ ] Implement proper error handling (don't leak information)
- [ ] Regular dependency updates
- [ ] Configure Web Application Firewall (WAF)

## Reporting Security Issues

If you discover a security vulnerability, please email the maintainers directly. Do not open public issues for security vulnerabilities.

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Azure Security Best Practices](https://docs.microsoft.com/en-us/azure/security/fundamentals/best-practices-and-patterns)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [Azure App Service Security](https://docs.microsoft.com/en-us/azure/app-service/overview-security)

## Version History

### 2025-10-17
- Initial security audit and improvements
- Disabled command execution endpoint
- Added XSS protection
- Implemented sensitive data filtering
- Removed hardcoded secrets
- Added SSL verification
- Implemented error handling
- Added security headers
