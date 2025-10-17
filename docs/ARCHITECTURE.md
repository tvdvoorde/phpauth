# Architecture Overview

## System Architecture

This project demonstrates a secure microservices architecture using Azure App Service with Azure Active Directory (AAD) authentication. The system consists of two main components:

### Components

```
┌─────────────────┐         ┌─────────────────┐
│   Frontend      │         │    Backend      │
│   Web App       │────────▶│      API        │
│  (thx1140front) │         │  (thx1140back)  │
└─────────────────┘         └─────────────────┘
         │                           │
         │                           │
         ▼                           ▼
┌─────────────────────────────────────────────┐
│     Azure Active Directory (AAD)             │
│  - Authentication                            │
│  - Authorization                             │
│  - Token Management                          │
└─────────────────────────────────────────────┘
         │
         ▼
┌─────────────────┐
│ Microsoft Graph │
│      API        │
└─────────────────┘
```

### Frontend Application (`front/`)

The frontend is a PHP web application that:
- Authenticates users via Azure Active Directory
- Displays debug information and environment details
- Obtains access tokens from AAD
- Makes authenticated requests to the backend API
- Implements OAuth 2.0 On-Behalf-Of (OBO) flow to access Microsoft Graph
- Retrieves user information from Microsoft Graph API

**Key Files:**
- `index.php` - Main page with authentication and API calls
- `process.php` - Command processing endpoint

### Backend Application (`back/`)

The backend is a simple PHP API that:
- Requires AAD authentication
- Returns server information in JSON format
- Protected by Azure App Service authentication

**Key Files:**
- `index.php` - API endpoint returning server hostname

## Authentication Flow

### 1. User Authentication
```
User → Frontend → AAD Login → Frontend (with ID token)
```

### 2. Backend API Access
```
Frontend → AAD (get access token for backend) → Backend API
```

### 3. On-Behalf-Of Flow for Microsoft Graph
```
Frontend → AAD (exchange token) → Microsoft Graph API
```

The On-Behalf-Of (OBO) flow allows the frontend to access Microsoft Graph on behalf of the authenticated user:

1. User authenticates and frontend receives an access token
2. Frontend exchanges this token with AAD using OBO grant type
3. AAD issues a new token with permissions to access Graph API
4. Frontend uses the new token to call Microsoft Graph

## Security Features

### Azure App Service Authentication

Both frontend and backend use Azure App Service's built-in authentication feature (Easy Auth), which provides:

- Automatic token validation
- Token refresh
- Session management
- Multiple identity provider support

### Token Types

1. **ID Token** - Contains user identity information (not used for API access)
2. **Access Token** - Used to access the backend API (audience: backend app ID)
3. **Graph Access Token** - Obtained via OBO flow to access Microsoft Graph

### API Permissions

- Frontend has API permissions on the backend
- Backend is configured to trust the frontend client
- Backend has delegated permissions for Microsoft Graph (with admin consent)

## Configuration Requirements

### App Registrations

Two Azure AD app registrations are required:

1. **Frontend App Registration**
   - Client ID: `eaf8e871-0239-4b27-825b-131bab583010`
   - Configured to request tokens for backend API
   - Login parameters include backend scope

2. **Backend App Registration**
   - Client ID: `fee96351-9eda-4fb7-a91b-ac46e9c07358`
   - Exposes API with scope: `user_impersonation`
   - Trusts frontend as an authorized client application
   - Has client secret for OBO flow
   - Configured with API access token version 2

### Token Audience Configuration

The system is configured to handle multiple audiences:
- Backend API tokens: `api://fee96351-9eda-4fb7-a91b-ac46e9c07358`
- Microsoft Graph tokens: obtained through OBO flow

## Deployment Model

### Azure Resources

- **Resource Group**: `phpauth`
- **App Service Plan**: `plan1` (S1 tier, Linux)
- **Web Apps**:
  - `thx1140front` (PHP 7.4)
  - `thx1140back` (PHP 7.4)

### Deployment Method

Applications are deployed using Azure CLI with ZIP deployment:
```bash
az webapp deployment source config-zip
```

## Network Security

- HTTPS is required for all communications
- Reverse proxy support with custom host header (`X-Original-Host`)
- Token store enabled for secure token management
- Unauthenticated requests redirect to login page
