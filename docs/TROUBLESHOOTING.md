# Troubleshooting Guide

## Common Issues and Solutions

### Authentication Issues

#### Issue: "AADSTS50105: The signed-in user is not assigned to a role"

**Cause**: User doesn't have permission to access the application.

**Solution**:
1. Go to Azure Portal → Azure Active Directory → Enterprise Applications
2. Find your application
3. Go to "Users and groups"
4. Assign the user to the application

#### Issue: "AADSTS65001: The user or administrator has not consented"

**Cause**: Required API permissions have not been granted.

**Solution**:
1. Go to Azure Portal → Azure Active Directory → App registrations
2. Select your application
3. Go to "API permissions"
4. Click "Grant admin consent for [tenant]"

#### Issue: Redirect loop or continuous authentication prompt

**Cause**: Authentication configuration mismatch between App Service and App Registration.

**Solution**:
1. Verify redirect URIs in app registration include:
   - `https://<app-name>.azurewebsites.net/.auth/login/aad/callback`
2. Check that authentication is properly enabled on the App Service
3. Verify the issuer URL matches your tenant

### Token Issues

#### Issue: "Invalid audience" or "Invalid token"

**Cause**: Token audience doesn't match the expected audience.

**Solution**:
1. For backend API calls, ensure token has audience `api://<backend-app-id>`
2. Check login parameters in frontend auth settings include the backend scope
3. Verify backend app's "Expose an API" configuration

**Verify token audience:**
```bash
# Decode token (use jwt.io or a JWT library)
echo "<token>" | cut -d. -f2 | base64 -d | jq .
```

#### Issue: "Access token has expired"

**Cause**: Token lifetime exceeded (default 1 hour).

**Solution**:
1. Implement token refresh logic in your application
2. Use the refresh token to obtain a new access token
3. For App Service, token refresh is automatic via `/.auth/refresh`

**Refresh token manually:**
```bash
curl -X GET https://<app-name>.azurewebsites.net/.auth/refresh
```

#### Issue: Cannot obtain token for Microsoft Graph

**Cause**: Missing permissions or admin consent for Graph API.

**Solution**:
1. Go to backend app registration → API permissions
2. Add Microsoft Graph delegated permissions: `openid`, `profile`, `email`
3. Click "Grant admin consent"
4. Verify client secret is correctly configured in the code

### Deployment Issues

#### Issue: "Service Unavailable" after deployment

**Cause**: Application failed to start or configuration error.

**Solution**:
1. Check App Service logs:
   ```bash
   az webapp log tail --name <app-name> --resource-group phpauth
   ```
2. Verify PHP version compatibility
3. Check file permissions in deployed ZIP
4. Review App Service diagnostics in Azure Portal

#### Issue: ZIP deployment fails

**Cause**: File size limits or network issues.

**Solution**:
1. Check ZIP file size (must be under 2GB)
2. Verify Azure CLI is authenticated:
   ```bash
   az account show
   ```
3. Try deployment with verbose logging:
   ```bash
   az webapp deployment source config-zip --resource-group phpauth \
     --name <app-name> --src <file>.zip --verbose
   ```

### API Call Issues

#### Issue: Backend API returns 401 Unauthorized

**Cause**: Missing or invalid authorization header.

**Solution**:
1. Verify access token is included in request:
   ```bash
   curl -v -H "Authorization: Bearer ${TOKEN}" https://<backend>.azurewebsites.net
   ```
2. Check that token has correct audience
3. Verify App Service authentication is enabled on backend

#### Issue: Cannot reach backend from frontend

**Cause**: Network configuration or CORS issues.

**Solution**:
1. Verify backend URL is correct in frontend code
2. Check that backend is accessible:
   ```bash
   curl https://<backend-name>.azurewebsites.net
   ```
3. Review App Service networking settings
4. Check firewall rules and IP restrictions

#### Issue: On-Behalf-Of flow fails

**Cause**: Missing client secret, incorrect configuration, or permission issues.

**Solution**:
1. Verify backend client secret is correct and not expired
2. Check that backend has delegated permissions for Microsoft Graph
3. Verify tenant ID in token endpoint URL is correct
4. Ensure admin consent has been granted

**Test OBO flow:**
```bash
curl -X POST \
  -F 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer' \
  -F 'client_id=<backend-app-id>' \
  -F 'client_secret=<secret>' \
  -F "assertion=${TOKEN}" \
  -F "scope=openid profile email" \
  -F "requested_token_use=on_behalf_of" \
  https://login.microsoftonline.com/<tenant-id>/oauth2/v2.0/token
```

### Configuration Issues

#### Issue: Environment variables not appearing

**Cause**: App Service environment variables not properly configured.

**Solution**:
1. Set application settings in Azure Portal:
   - App Service → Configuration → Application settings
2. Or use Azure CLI:
   ```bash
   az webapp config appsettings set --resource-group phpauth \
     --name <app-name> --settings KEY=VALUE
   ```
3. Restart the app after configuration changes

#### Issue: Custom domain SSL issues

**Cause**: SSL certificate not properly configured.

**Solution**:
1. Verify SSL binding in App Service
2. Check that SSL certificate is valid and not expired
3. Use Azure-managed certificate for simplicity:
   ```bash
   az webapp config ssl create --resource-group phpauth \
     --name <app-name> --hostname <custom-domain>
   ```

### Debugging Tips

#### Enable Application Insights

1. Go to Azure Portal → App Service → Application Insights
2. Click "Turn on Application Insights"
3. View logs and telemetry in Application Insights portal

#### Enable Detailed Error Messages

For PHP applications:

1. Edit `index.php` to add at the top:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Check PHP error logs in App Service

#### View App Service Logs

```bash
# Stream logs in real-time
az webapp log tail --name <app-name> --resource-group phpauth

# Download logs
az webapp log download --resource-group phpauth --name <app-name>
```

#### Inspect HTTP Headers

Add debug output in PHP to inspect headers:

```php
<?php
foreach (getallheaders() as $name => $value) {
    error_log("$name: $value");
}
?>
```

#### Test Authentication Manually

```bash
# Get token from /.auth/me endpoint
curl https://<frontend-name>.azurewebsites.net/.auth/me

# Test backend with token
TOKEN=$(curl -s https://<frontend-name>.azurewebsites.net/.auth/me | jq -r '.[0].access_token')
curl -H "Authorization: Bearer ${TOKEN}" https://<backend-name>.azurewebsites.net
```

### Performance Issues

#### Issue: Slow response times

**Cause**: Cold start, inefficient code, or network latency.

**Solution**:
1. Enable "Always On" in App Service configuration
2. Scale up the App Service Plan for better performance
3. Implement caching for tokens and API responses
4. Use Application Insights to identify bottlenecks

#### Issue: High memory usage

**Cause**: Memory leaks or inefficient code.

**Solution**:
1. Review PHP code for memory leaks
2. Scale up to a larger App Service Plan
3. Monitor memory usage in Azure Portal

### Security Issues

#### Issue: Secrets exposed in code

**Cause**: Hardcoded secrets in source files.

**Solution**:
1. Move secrets to App Service configuration (Application Settings)
2. Use Azure Key Vault for sensitive secrets
3. Reference secrets via environment variables:
   ```php
   $client_secret = getenv('BACKEND_CLIENT_SECRET');
   ```
4. Never commit secrets to source control

#### Issue: HTTPS not enforced

**Cause**: App Service allows HTTP traffic.

**Solution**:
1. Go to Azure Portal → App Service → TLS/SSL settings
2. Enable "HTTPS Only"
3. Or use Azure CLI:
   ```bash
   az webapp update --resource-group phpauth \
     --name <app-name> --https-only true
   ```

## Getting Help

### Azure Support Resources

- [Azure Documentation](https://docs.microsoft.com/azure/)
- [Azure Support Plans](https://azure.microsoft.com/support/plans/)
- [Azure Community Forums](https://docs.microsoft.com/answers/products/azure)
- [Stack Overflow](https://stackoverflow.com/questions/tagged/azure)

### Diagnostic Tools

1. **Kudu Console**: `https://<app-name>.scm.azurewebsites.net`
2. **App Service Diagnostics**: Azure Portal → App Service → Diagnose and solve problems
3. **Application Insights**: Azure Portal → Application Insights
4. **Log Stream**: Azure Portal → App Service → Log stream

### Log Collection

When reporting issues, collect the following:

1. App Service logs (HTTP logs, application logs)
2. Application Insights traces
3. Failed request traces
4. Request/response headers and bodies
5. Error messages and stack traces
6. Configuration settings (sanitize secrets)

### Useful Commands

```bash
# Check app status
az webapp show --resource-group phpauth --name <app-name> --query state

# Restart app
az webapp restart --resource-group phpauth --name <app-name>

# View configuration
az webapp config show --resource-group phpauth --name <app-name>

# View authentication settings
az webapp auth show --resource-group phpauth --name <app-name>

# Check app service plan
az appservice plan show --resource-group phpauth --name plan1
```

## Additional Resources

- [SETUP.md](SETUP.md) - Setup and configuration guide
- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture documentation
- [API.md](API.md) - API reference
