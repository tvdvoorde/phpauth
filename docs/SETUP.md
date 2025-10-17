# Setup and Configuration Guide

## Prerequisites

- Azure subscription
- Azure CLI installed and configured
- PHP 7.4 or higher
- Git
- Basic understanding of OAuth 2.0 and Azure Active Directory

## Step-by-Step Setup

### 1. Create Azure Resources

First, create the resource group and App Service plan:

```bash
# Create resource group
az group create --name phpauth --location "West Europe"

# Create App Service plan (S1 tier, Linux)
az appservice plan create --name plan1 --resource-group phpauth --sku S1 --is-linux

# Create frontend web app
az webapp create --resource-group phpauth --plan plan1 --name thx1140front --runtime "PHP|7.4" 

# Create backend web app
az webapp create --resource-group phpauth --plan plan1 --name thx1140back --runtime "PHP|7.4" 
```

**Note:** Replace `thx1140front` and `thx1140back` with unique names for your deployment.

### 2. Deploy Applications

Deploy the frontend and backend code:

```bash
# Deploy backend
rm -f back.zip
zip -j back.zip back/*.*
az webapp deployment source config-zip --resource-group phpauth --name thx1140back --src back.zip

# Deploy frontend
rm -f front.zip
zip -j front.zip front/*.*
az webapp deployment source config-zip --resource-group phpauth --name thx1140front --src front.zip
```

### 3. Configure Azure Active Directory

#### 3.1 Create App Registrations

Create two app registrations in Azure Active Directory:

1. **Backend App Registration** (`thx1140back`)
   - Navigate to Azure Portal → Azure Active Directory → App registrations
   - Click "New registration"
   - Name: `thx1140back`
   - Note the **Application (client) ID** (backend app ID)

2. **Frontend App Registration** (`thx1140front`)
   - Create another app registration
   - Name: `thx1140front`
   - Note the **Application (client) ID** (frontend app ID)

#### 3.2 Expose Backend API

Configure the backend to expose an API:

1. Go to backend app registration
2. Navigate to "Expose an API"
3. Click "Add a scope"
4. Application ID URI: `api://<backend-app-id>`
5. Scope name: `user_impersonation`
6. Admin consent display name: "Access backend on behalf of user"
7. Save the scope

#### 3.3 Add Authorized Client Application

In the backend app registration:

1. Navigate to "Expose an API"
2. Click "Add a client application"
3. Enter the frontend app client ID
4. Check the `user_impersonation` scope
5. Click "Add application"

#### 3.4 Configure Frontend API Permissions

In the frontend app registration:

1. Navigate to "API permissions"
2. Click "Add a permission"
3. Select "My APIs"
4. Select the backend app
5. Check `user_impersonation` permission
6. Click "Add permissions"
7. Click "Grant admin consent" (requires admin privileges)

#### 3.5 Configure Backend Client Secret

The backend needs a client secret for the On-Behalf-Of flow:

1. Go to backend app registration
2. Navigate to "Certificates & secrets"
3. Click "New client secret"
4. Add description and expiration period
5. Copy the secret value (you'll need this for the code)

#### 3.6 Grant Graph API Permissions

Configure the backend to access Microsoft Graph:

1. Go to backend app registration
2. Navigate to "API permissions"
3. Click "Add a permission"
4. Select "Microsoft Graph"
5. Select "Delegated permissions"
6. Add: `openid`, `profile`, `email`
7. Click "Add permissions"
8. Click "Grant admin consent"

#### 3.7 Configure Access Token Version

Set the backend to use access token version 2:

```bash
# Get the backend app object ID
id=$(az ad app show --id <backend-app-id> --query objectId --output tsv)

# Update to version 2
az rest --method PATCH \
  --url https://graph.microsoft.com/v1.0/applications/$id \
  --body "{'api':{'requestedAccessTokenVersion':2}}"
```

### 4. Enable Azure App Service Authentication

#### 4.1 Enable Authentication on Backend

1. Navigate to Azure Portal → App Services → `thx1140back`
2. Click "Authentication" in the left menu
3. Click "Add identity provider"
4. Select "Microsoft"
5. Configure:
   - App registration type: Use existing app registration
   - Application (client) ID: Enter backend app ID
   - Client secret: Configure in application settings
   - Issuer URL: `https://sts.windows.net/<tenant-id>/v2.0`
   - Allowed audiences: Add `api://<backend-app-id>`
   - Restrict access: Require authentication
   - Unauthenticated requests: HTTP 401 Unauthorized (recommended for APIs)
6. Click "Add"

#### 4.2 Enable Authentication on Frontend

1. Navigate to Azure Portal → App Services → `thx1140front`
2. Click "Authentication"
3. Click "Add identity provider"
4. Select "Microsoft"
5. Configure:
   - App registration type: Use existing app registration
   - Application (client) ID: Enter frontend app ID
   - Restrict access: Require authentication
   - Unauthenticated requests: Redirect to login page
6. Click "Add"

### 5. Configure Frontend Login Parameters

The frontend needs to request tokens for the backend API:

```bash
# Get current auth settings
authSettings=$(az webapp auth show -g phpauth -n thx1140front)

# Add login parameters to request backend scope
authSettings=$(echo "$authSettings" | jq '.properties' | \
  jq '.identityProviders.azureActiveDirectory.login += {
    "loginParameters":[
      "scope=openid profile email offline_access api://<backend-app-id>/user_impersonation"
    ]
  }')

# Update auth settings
az webapp auth set --resource-group phpauth --name thx1140front --body "$authSettings"
```

Replace `<backend-app-id>` with your actual backend application client ID.

### 6. Update Application Code

#### 6.1 Update Backend Configuration

No code changes are typically needed for the backend, as it uses Azure App Service authentication.

#### 6.2 Update Frontend Configuration

In `front/index.php`, update the following values:

1. **Backend URL** (line 48):
   ```php
   $url = "https://<your-backend-name>.azurewebsites.net";
   ```

2. **Tenant ID** (line 65):
   ```php
   curl_setopt($ch, CURLOPT_URL, 
     "https://login.microsoftonline.com/<your-tenant-id>/oauth2/v2.0/token");
   ```

3. **Backend App ID and Secret** (lines 68-69):
   ```php
   ."&client_id=".urlencode("<your-backend-app-id>")
   ."&client_secret=".urlencode("<your-backend-client-secret>")
   ```

### 7. Deploy Updated Code

After updating the configuration, redeploy the frontend:

```bash
rm -f front.zip
zip -j front.zip front/*.*
az webapp deployment source config-zip --resource-group phpauth --name thx1140front --src front.zip
```

## Verification

### Test Backend API

Get an access token and test the backend:

```bash
# Navigate to frontend and copy the access token from the page
TOKEN="<access-token-from-frontend>"

# Test backend API
curl -H "Authorization: Bearer ${TOKEN}" https://<your-backend-name>.azurewebsites.net
```

Expected response:
```json
{ "server": "hostname" }
```

### Test Frontend

1. Navigate to `https://<your-frontend-name>.azurewebsites.net`
2. You should be redirected to Azure AD login
3. After login, you should see:
   - Debug information
   - Access token
   - Backend API response
   - Graph API token
   - User information from Microsoft Graph

## Troubleshooting

See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues and solutions.

## Security Considerations

1. **Never commit secrets to source control** - Use Azure Key Vault or App Settings for secrets
2. **Rotate secrets regularly** - Set expiration dates on client secrets
3. **Use managed identities** where possible - Reduces secret management overhead
4. **Review API permissions** - Only grant minimum required permissions
5. **Monitor authentication logs** - Use Azure Monitor and Application Insights

## Configuration Files

### auth.json

The `auth.json` file is a sample configuration for Azure App Service authentication settings. It demonstrates:

- Identity provider configuration (AAD, Facebook, Google, GitHub, etc.)
- Token store settings
- Cookie expiration settings
- Allowed redirect URLs
- Reverse proxy configuration

This file can be used with Azure CLI to configure authentication:

```bash
az rest --uri /subscriptions/<subscription-id>/resourceGroups/<resource-group>/providers/Microsoft.Web/sites/<app-name>/config/authsettingsV2?api-version=2020-09-01 --method put --body @auth.json
```

## Next Steps

- Review [ARCHITECTURE.md](ARCHITECTURE.md) to understand the system design
- Check [API.md](API.md) for API documentation
- See [DEVELOPMENT.md](DEVELOPMENT.md) for local development setup
