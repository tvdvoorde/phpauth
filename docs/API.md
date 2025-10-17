# API Reference

## Backend API

### Base URL
```
https://<backend-name>.azurewebsites.net
```

### Authentication

All requests must include a valid Azure AD access token in the Authorization header:

```http
Authorization: Bearer <access-token>
```

The access token must:
- Be issued by Azure Active Directory
- Have the audience set to `api://<backend-app-id>`
- Be obtained through Azure App Service authentication or OAuth 2.0 flow

### Endpoints

#### GET /

Returns server information.

**Request:**
```http
GET / HTTP/1.1
Host: <backend-name>.azurewebsites.net
Authorization: Bearer <access-token>
```

**Response:**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "server": "hostname"
}
```

**Response Fields:**
- `server` (string): The hostname of the server processing the request

**Status Codes:**
- `200 OK`: Request successful
- `401 Unauthorized`: Missing or invalid access token
- `403 Forbidden`: Token valid but insufficient permissions

**Example with cURL:**
```bash
curl -H "Authorization: Bearer ${TOKEN}" \
  https://thx1140back.azurewebsites.net
```

## Frontend Application

### Base URL
```
https://<frontend-name>.azurewebsites.net
```

### Authentication

The frontend uses Azure App Service authentication. Users are automatically redirected to Azure AD login if not authenticated.

### Pages

#### GET /index.php

Main application page displaying debug information and demonstrating API calls.

**Access:** Requires authentication

**Features:**
- Displays environment variables
- Shows HTTP headers
- Displays access token
- Makes authenticated call to backend API
- Performs On-Behalf-Of flow to get Graph token
- Retrieves user information from Microsoft Graph

**Sections:**
1. **Debug Information**
   - Request URL
   - Outbound public IP
   - Inbound client IP
   - Hostname
   - Operating system information

2. **Environment Variables**
   - Lists all environment variables

3. **HTTP Headers**
   - Displays all request headers
   - Extracts `X-MS-TOKEN-AAD-ACCESS-TOKEN` for API calls

4. **Access Token**
   - Shows the current access token

5. **Backend API Response**
   - Makes call to backend API
   - Displays JSON response

6. **Graph Access Token**
   - Performs OAuth 2.0 On-Behalf-Of flow
   - Obtains token for Microsoft Graph

7. **Microsoft Graph Response**
   - Calls Graph API `/oidc/userinfo` endpoint
   - Displays user information

#### POST /process.php

Command execution endpoint (restricted to bash commands).

**Request:**
```http
POST /process.php HTTP/1.1
Host: <frontend-name>.azurewebsites.net
Content-Type: application/x-www-form-urlencoded

name=/bin/bash+-c+<command>
```

**Parameters:**
- `name` (string): Command to execute (must start with `/bin/bash`)

**Response:**
```http
HTTP/1.1 200 OK
Content-Type: text/html

Returned with status <code> and output:
<output lines>
```

**Security Note:** This endpoint only executes commands that start with `/bin/bash`. It's intended for demonstration purposes and should be secured or removed in production.

## Azure App Service Authentication Endpoints

### GET /.auth/me

Returns information about the current authenticated user and their tokens.

**Request:**
```http
GET /.auth/me HTTP/1.1
Host: <app-name>.azurewebsites.net
```

**Response:**
```json
[
  {
    "access_token": "<access-token>",
    "expires_on": "<expiration-datetime>",
    "id_token": "<id-token>",
    "provider_name": "aad",
    "user_claims": [
      {
        "typ": "claim-type",
        "val": "claim-value"
      }
    ],
    "user_id": "<user-id>"
  }
]
```

### GET /.auth/login/aad

Initiates Azure AD login flow.

**Parameters:**
- `post_login_redirect_uri` (optional): URL to redirect after successful login

### GET /.auth/logout

Logs out the current user and clears the authentication session.

**Parameters:**
- `post_logout_redirect_uri` (optional): URL to redirect after logout

### GET /.auth/refresh

Refreshes the current authentication tokens.

## OAuth 2.0 Token Endpoints

### On-Behalf-Of Token Request

Request a new access token on behalf of the authenticated user.

**Endpoint:**
```
POST https://login.microsoftonline.com/<tenant-id>/oauth2/v2.0/token
```

**Request Body:**
```
grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer
client_id=<backend-app-id>
client_secret=<backend-client-secret>
assertion=<user-access-token>
requested_token_use=on_behalf_of
scope=openid profile email
```

**Response:**
```json
{
  "access_token": "<new-access-token>",
  "token_type": "Bearer",
  "expires_in": 3599,
  "scope": "openid profile email",
  "refresh_token": "<refresh-token>"
}
```

**Example:**
```bash
response=$(curl -X POST \
  -F 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer' \
  -F 'client_id=<backend-app-id>' \
  -F 'client_secret=<backend-secret>' \
  -F "assertion=${TOKEN}" \
  -F "scope=openid profile email" \
  -F "requested_token_use=on_behalf_of" \
  https://login.microsoftonline.com/<tenant-id>/oauth2/v2.0/token \
  | jq -r ".access_token")
```

## Microsoft Graph API Endpoints

### GET /oidc/userinfo

Returns OpenID Connect user information.

**Endpoint:**
```
https://graph.microsoft.com/oidc/userinfo
```

**Authentication:**
```http
Authorization: Bearer <graph-access-token>
```

**Response:**
```json
{
  "sub": "<user-id>",
  "name": "User Name",
  "family_name": "Name",
  "given_name": "User",
  "email": "user@example.com"
}
```

**Example:**
```bash
curl -H "Authorization: Bearer ${GRAPH_TOKEN}" \
  https://graph.microsoft.com/oidc/userinfo
```

## Error Responses

### Authentication Errors

**401 Unauthorized**
```json
{
  "error": "invalid_token",
  "error_description": "The access token is invalid or expired"
}
```

**403 Forbidden**
```json
{
  "error": "insufficient_permissions",
  "error_description": "The token does not have the required permissions"
}
```

### API Errors

**500 Internal Server Error**
```json
{
  "error": "server_error",
  "error_description": "An unexpected error occurred"
}
```

## Rate Limits

Azure App Service and Microsoft Graph API have rate limits:

- **Azure AD token endpoint**: 1000 requests per 5 minutes per tenant
- **Microsoft Graph API**: Varies by endpoint, typically 10,000 requests per 10 minutes

## Best Practices

1. **Token Caching**: Cache access tokens and reuse them until they expire (typically 1 hour)
2. **Token Refresh**: Use refresh tokens to obtain new access tokens without user interaction
3. **Error Handling**: Implement retry logic with exponential backoff for transient errors
4. **Scope Management**: Only request the minimum scopes required for your application
5. **Token Validation**: Always validate tokens server-side, never trust client-provided tokens

## Security Headers

Required headers for authenticated requests:

```http
Authorization: Bearer <access-token>
Content-Type: application/json
Accept: application/json
```

For Azure App Service, the platform automatically injects:
- `X-MS-TOKEN-AAD-ACCESS-TOKEN`: Access token
- `X-MS-TOKEN-AAD-ID-TOKEN`: ID token
- `X-MS-TOKEN-AAD-REFRESH-TOKEN`: Refresh token
- `X-MS-CLIENT-PRINCIPAL`: Base64-encoded user information

## Token Claims

### Access Token Claims

Important claims in the access token:

- `aud`: Audience (should match your backend API app ID)
- `iss`: Issuer (Azure AD)
- `iat`: Issued at time
- `exp`: Expiration time
- `scp`: Scopes granted
- `sub`: Subject (user identifier)
- `tid`: Tenant ID

### ID Token Claims

Important claims in the ID token:

- `aud`: Audience (should match your frontend app ID)
- `name`: User's display name
- `email`: User's email address
- `oid`: Object ID (unique user identifier)
- `preferred_username`: User's preferred username
- `tid`: Tenant ID

## Additional Resources

- [Microsoft Graph API Documentation](https://docs.microsoft.com/en-us/graph/)
- [Azure AD OAuth 2.0 Documentation](https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow)
- [Azure App Service Authentication Documentation](https://docs.microsoft.com/en-us/azure/app-service/overview-authentication-authorization)
