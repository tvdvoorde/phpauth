# PHP Authentication with Azure Active Directory

A demonstration project showing how to implement secure authentication and authorization using Azure Active Directory (AAD) with Azure App Service. This project showcases a microservices architecture with a PHP frontend and backend, including OAuth 2.0 On-Behalf-Of (OBO) flow for accessing Microsoft Graph API.

## Overview

This project demonstrates:

- **Azure App Service Authentication** - Built-in authentication with Azure Active Directory
- **Microservices Architecture** - Separate frontend and backend applications
- **Token-based Authentication** - OAuth 2.0 access tokens for API security
- **On-Behalf-Of Flow** - Accessing Microsoft Graph API on behalf of authenticated users
- **API Security** - Securing backend APIs with AAD tokens

### Architecture

```
┌─────────────────┐         ┌─────────────────┐
│   Frontend      │         │    Backend      │
│   Web App       │────────▶│      API        │
│  (thx1140front) │         │  (thx1140back)  │
└─────────────────┘         └─────────────────┘
         │                           │
         ▼                           ▼
┌─────────────────────────────────────────────┐
│     Azure Active Directory (AAD)             │
└─────────────────────────────────────────────┘
         │
         ▼
┌─────────────────┐
│ Microsoft Graph │
└─────────────────┘
```

## Key Features

- ✅ Azure App Service built-in authentication (Easy Auth)
- ✅ Azure Active Directory integration
- ✅ Secure token management with automatic refresh
- ✅ OAuth 2.0 On-Behalf-Of flow for delegated API access
- ✅ Microsoft Graph API integration
- ✅ Backend API protection with token validation
- ✅ Debug utilities for development and troubleshooting

## Quick Start

### Prerequisites

- Azure subscription
- Azure CLI installed
- PHP 7.4 or higher (for local development)

### 1. Deploy Infrastructure

```bash
# Create resource group
az group create --name phpauth --location "West Europe"

# Create App Service plan
az appservice plan create --name plan1 --resource-group phpauth --sku S1 --is-linux

# Create web apps
az webapp create --resource-group phpauth --plan plan1 --name thx1140front --runtime "PHP|7.4" 
az webapp create --resource-group phpauth --plan plan1 --name thx1140back --runtime "PHP|7.4" 
```

### 2. Deploy Applications

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

### 3. Configure Authentication

For detailed setup instructions, see the [Setup Guide](docs/SETUP.md).

**Quick summary:**
1. Create Azure AD app registrations for frontend and backend
2. Configure backend to expose an API
3. Grant frontend permissions to access backend
4. Enable App Service authentication on both apps
5. Configure login parameters for token audience

## Documentation

Comprehensive documentation is available in the `docs/` directory:

- **[Setup Guide](docs/SETUP.md)** - Complete setup and configuration instructions
- **[Architecture](docs/ARCHITECTURE.md)** - System architecture and design
- **[API Reference](docs/API.md)** - API endpoints and usage
- **[Development Guide](docs/DEVELOPMENT.md)** - Local development and contributing
- **[Troubleshooting](docs/TROUBLESHOOTING.md)** - Common issues and solutions

## Project Structure

```
phpauth/
├── back/                   # Backend API application
│   └── index.php          # Main API endpoint
├── front/                  # Frontend web application
│   ├── index.php          # Main page with authentication
│   └── process.php        # Command processor
├── docs/                   # Documentation
├── auth.json              # Sample authentication configuration
└── README.md              # This file
```

## Components

### Frontend Application

The frontend (`front/`) is a PHP web application that:
- Authenticates users via Azure Active Directory
- Displays debug information and environment details
- Makes authenticated requests to the backend API
- Implements OAuth 2.0 On-Behalf-Of flow
- Retrieves user information from Microsoft Graph

### Backend Application

The backend (`back/`) is a simple PHP API that:
- Requires Azure AD authentication
- Returns server information in JSON format
- Is protected by Azure App Service authentication

## Authentication Flow

1. **User Authentication**: User logs in via Azure AD through the frontend
2. **Access Token**: Frontend obtains an access token for the backend API
3. **API Call**: Frontend calls backend API with the access token
4. **On-Behalf-Of Flow**: Frontend exchanges token to access Microsoft Graph
5. **Graph API**: Frontend retrieves user data from Microsoft Graph

## Security Features

- 🔒 Azure App Service built-in authentication
- 🔒 Automatic token validation and refresh
- 🔒 Token-based API authorization
- 🔒 HTTPS enforced for all communications
- 🔒 Secure token storage
- 🔒 Client secret management via Azure Key Vault (recommended)

## Testing

Test the backend API with a token:

```bash
# Get token from the frontend or /.auth/me endpoint
TOKEN="your-access-token"

# Test backend
curl -H "Authorization: Bearer ${TOKEN}" https://thx1140back.azurewebsites.net
```

Expected response:
```json
{ "server": "hostname" }
```

## Configuration

Key configuration values to customize:

- `thx1140front` / `thx1140back` - Replace with your unique app names
- Frontend app ID: `eaf8e871-0239-4b27-825b-131bab583010`
- Backend app ID: `fee96351-9eda-4fb7-a91b-ac46e9c07358`
- Tenant ID: Update in frontend code for OBO flow

See [Setup Guide](docs/SETUP.md) for detailed configuration instructions.

## Development

For local development and contributing:

1. Clone the repository
2. Review the [Development Guide](docs/DEVELOPMENT.md)
3. Configure Azure AD for your development environment
4. Deploy to Azure for testing (local auth is limited)

## Troubleshooting

Common issues and solutions:

- **Authentication errors** - Check app registration configuration
- **Token issues** - Verify audience and permissions
- **API call failures** - Check authorization headers and backend authentication

See the [Troubleshooting Guide](docs/TROUBLESHOOTING.md) for detailed solutions.

## Resources

### Microsoft Documentation

- [Azure App Service Authentication](https://docs.microsoft.com/en-us/azure/app-service/overview-authentication-authorization)
- [Azure AD OAuth 2.0](https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow)
- [On-Behalf-Of Flow](https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-on-behalf-of-flow)
- [Microsoft Graph API](https://docs.microsoft.com/en-us/graph/)

### Related Articles

- [OAuth 2.0 Token Types](https://auth0.com/docs/secure/tokens/access-tokens)
- [Multiple Backend Services](https://www.ludovicmedard.com/azure-api-management-and-oauth-tokens-for-multiple-backend-services/)
- [Custom Domains with App Service](https://azure.github.io/AppService/2021/03/26/Secure-resilient-site-with-custom-domain.html)

## Important Security Notes

⚠️ **ID Tokens vs Access Tokens**: ID Tokens should not be used to access APIs. Per the OpenID Connect specification, the audience (aud claim) of the ID Token must be the client ID of the application. APIs require access tokens with the API's unique identifier as the audience.

⚠️ **Client Secrets**: Never commit secrets to source control. Use Azure Key Vault or App Service configuration for secret management.

⚠️ **Production Security**: The `process.php` endpoint is for demonstration purposes only. Secure or remove it in production environments.

## License

This is a demonstration project for educational purposes.

## Contributing

Contributions are welcome! Please see the [Development Guide](docs/DEVELOPMENT.md) for guidelines.

## Support

For issues and questions:
- Check the [Troubleshooting Guide](docs/TROUBLESHOOTING.md)
- Review [Azure documentation](https://docs.microsoft.com/azure/)
- Open an issue on GitHub
