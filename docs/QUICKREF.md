# Quick Reference

Quick reference guide for common tasks and important information.

## App IDs and Configuration

| Component | App ID | Purpose |
|-----------|--------|---------|
| Frontend | `eaf8e871-0239-4b27-825b-131bab583010` | User-facing web application |
| Backend | `fee96351-9eda-4fb7-a91b-ac46e9c07358` | Protected API backend |

**Note**: These are example IDs from the demo. Replace with your own app registration IDs.

## Common Commands

### Deployment

```bash
# Deploy backend
rm -f back.zip && zip -j back.zip back/*.* && \
az webapp deployment source config-zip \
  --resource-group phpauth --name thx1140back --src back.zip

# Deploy frontend
rm -f front.zip && zip -j front.zip front/*.* && \
az webapp deployment source config-zip \
  --resource-group phpauth --name thx1140front --src front.zip
```

### Monitoring

```bash
# Stream logs
az webapp log tail --resource-group phpauth --name <app-name>

# Download logs
az webapp log download --resource-group phpauth --name <app-name>

# Check app status
az webapp show --resource-group phpauth --name <app-name> --query state
```

### Configuration

```bash
# View auth settings
az webapp auth show --resource-group phpauth --name <app-name>

# Set environment variable
az webapp config appsettings set \
  --resource-group phpauth --name <app-name> \
  --settings KEY=VALUE

# Restart app
az webapp restart --resource-group phpauth --name <app-name>
```

## Authentication Endpoints

| Endpoint | Purpose |
|----------|---------|
| `/.auth/login/aad` | Initiate Azure AD login |
| `/.auth/logout` | Logout current user |
| `/.auth/me` | Get current user info and tokens |
| `/.auth/refresh` | Refresh authentication tokens |

## API Scopes

### Frontend to Backend

```
api://fee96351-9eda-4fb7-a91b-ac46e9c07358/user_impersonation
```

### On-Behalf-Of for Graph

```
scope=openid profile email
```

## Token Validation

### Decode JWT Token

```bash
# Using command line
echo "<token>" | cut -d. -f2 | base64 -d | jq .

# Or use jwt.io
```

### Important Token Claims

- `aud`: Audience (must match API app ID)
- `iss`: Issuer (Azure AD)
- `exp`: Expiration timestamp
- `scp`: Scopes granted
- `oid`: User's object ID

## Testing

### Test Backend API

```bash
# Get token from frontend
TOKEN=$(curl -s https://<frontend>.azurewebsites.net/.auth/me | jq -r '.[0].access_token')

# Call backend
curl -H "Authorization: Bearer ${TOKEN}" https://<backend>.azurewebsites.net

# Expected: {"server":"hostname"}
```

### Test On-Behalf-Of Flow

```bash
curl -X POST \
  -F 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer' \
  -F 'client_id=<backend-app-id>' \
  -F 'client_secret=<secret>' \
  -F "assertion=${TOKEN}" \
  -F "scope=openid profile email" \
  -F "requested_token_use=on_behalf_of" \
  https://login.microsoftonline.com/<tenant>/oauth2/v2.0/token
```

### Test Graph API

```bash
# Get Graph token via OBO
GRAPH_TOKEN=$(curl -X POST \
  -F 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer' \
  -F 'client_id=<backend-app-id>' \
  -F 'client_secret=<secret>' \
  -F "assertion=${TOKEN}" \
  -F "scope=openid profile email" \
  -F "requested_token_use=on_behalf_of" \
  https://login.microsoftonline.com/<tenant>/oauth2/v2.0/token | jq -r '.access_token')

# Call Graph API
curl -H "Authorization: Bearer ${GRAPH_TOKEN}" \
  https://graph.microsoft.com/oidc/userinfo
```

## Environment Variables

### Frontend

```bash
BACKEND_URL=https://thx1140back.azurewebsites.net
TENANT_ID=<your-tenant-id>
BACKEND_APP_ID=fee96351-9eda-4fb7-a91b-ac46e9c07358
BACKEND_CLIENT_SECRET=<secret>
```

Set via Azure CLI:
```bash
az webapp config appsettings set --resource-group phpauth \
  --name thx1140front \
  --settings BACKEND_URL="https://thx1140back.azurewebsites.net" \
             TENANT_ID="<tenant-id>" \
             BACKEND_APP_ID="<app-id>" \
             BACKEND_CLIENT_SECRET="@Microsoft.KeyVault(SecretUri=<vault-url>)"
```

## Troubleshooting Quick Fixes

### Issue: 401 Unauthorized

```bash
# Check if authentication is enabled
az webapp auth show --resource-group phpauth --name <app-name> | jq '.properties.globalValidation.requireAuthentication'

# Should return: true
```

### Issue: Wrong audience in token

```bash
# Update frontend login parameters
authSettings=$(az webapp auth show -g phpauth -n thx1140front)
authSettings=$(echo "$authSettings" | jq '.properties' | \
  jq '.identityProviders.azureActiveDirectory.login += {
    "loginParameters":["scope=openid profile email offline_access api://<backend-app-id>/user_impersonation"]
  }')
az webapp auth set --resource-group phpauth --name thx1140front --body "$authSettings"
```

### Issue: Token expired

```bash
# Refresh token
curl https://<app-name>.azurewebsites.net/.auth/refresh
```

### Issue: App not starting

```bash
# Check logs
az webapp log tail --resource-group phpauth --name <app-name>

# Restart app
az webapp restart --resource-group phpauth --name <app-name>
```

## Security Checklist

- [ ] Client secrets stored in Azure Key Vault
- [ ] HTTPS only enabled on all apps
- [ ] Admin consent granted for all API permissions
- [ ] Token audience configured correctly
- [ ] Access token version set to 2
- [ ] IP restrictions configured (if needed)
- [ ] Application Insights enabled for monitoring
- [ ] Always On enabled for production
- [ ] Remove or secure `process.php` endpoint

## Useful Links

- [Azure Portal](https://portal.azure.com)
- [Azure AD Admin Center](https://aad.portal.azure.com)
- [Graph Explorer](https://developer.microsoft.com/graph/graph-explorer)
- [JWT Decoder](https://jwt.io)
- [Azure CLI Reference](https://docs.microsoft.com/cli/azure/)

## Configuration Files

### .gitignore (recommended)

```gitignore
*.zip
*.log
.env
.vscode/
.idea/
*.secret
```

### Local Development .env

```env
BACKEND_URL=https://thx1140back.azurewebsites.net
TENANT_ID=your-tenant-id
BACKEND_APP_ID=fee96351-9eda-4fb7-a91b-ac46e9c07358
BACKEND_CLIENT_SECRET=your-client-secret
```

## HTTP Headers

### Request Headers to Backend

```http
GET / HTTP/1.1
Host: thx1140back.azurewebsites.net
Authorization: Bearer <access-token>
Accept: application/json
```

### Azure App Service Injected Headers

```http
X-MS-TOKEN-AAD-ACCESS-TOKEN: <access-token>
X-MS-TOKEN-AAD-ID-TOKEN: <id-token>
X-MS-TOKEN-AAD-REFRESH-TOKEN: <refresh-token>
X-MS-CLIENT-PRINCIPAL: <base64-encoded-user-info>
X-MS-CLIENT-PRINCIPAL-ID: <user-id>
X-MS-CLIENT-PRINCIPAL-NAME: <user-email>
```

## Resource URLs

### Azure Resources

- **Resource Group**: `phpauth`
- **App Service Plan**: `plan1` (S1 SKU, Linux)
- **Frontend App**: `https://thx1140front.azurewebsites.net`
- **Backend App**: `https://thx1140back.azurewebsites.net`

### Azure AD Endpoints

- **Login**: `https://login.microsoftonline.com/<tenant-id>`
- **Token**: `https://login.microsoftonline.com/<tenant-id>/oauth2/v2.0/token`
- **Authorize**: `https://login.microsoftonline.com/<tenant-id>/oauth2/v2.0/authorize`

### Microsoft Graph

- **Base URL**: `https://graph.microsoft.com`
- **User Info**: `https://graph.microsoft.com/oidc/userinfo`
- **Profile**: `https://graph.microsoft.com/v1.0/me`

## Performance Tips

1. **Enable OPcache**: Improves PHP performance
2. **Cache tokens**: Reduce authentication calls
3. **Use CDN**: For static assets
4. **Enable Always On**: Eliminates cold starts
5. **Scale up/out**: Based on load requirements

## Support and Documentation

- **Setup Guide**: [docs/SETUP.md](SETUP.md)
- **Architecture**: [docs/ARCHITECTURE.md](ARCHITECTURE.md)
- **API Reference**: [docs/API.md](API.md)
- **Development**: [docs/DEVELOPMENT.md](DEVELOPMENT.md)
- **Troubleshooting**: [docs/TROUBLESHOOTING.md](TROUBLESHOOTING.md)

## Emergency Procedures

### Revoke Access

```bash
# Disable authentication temporarily
az webapp auth update --resource-group phpauth \
  --name <app-name> --enabled false

# Re-enable after investigation
az webapp auth update --resource-group phpauth \
  --name <app-name> --enabled true
```

### Rotate Client Secret

1. Create new secret in Azure AD app registration
2. Update App Service configuration with new secret
3. Test thoroughly
4. Delete old secret

### Rollback Deployment

```bash
# List deployment history
az webapp deployment list --resource-group phpauth --name <app-name>

# Deploy previous version
az webapp deployment source config-zip --resource-group phpauth \
  --name <app-name> --src <previous-version>.zip
```
