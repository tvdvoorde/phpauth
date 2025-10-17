# Development Guide

## Local Development Setup

### Prerequisites

- PHP 7.4 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- cURL extension for PHP
- Azure subscription for testing authentication
- Git

### Setting Up Local Environment

#### 1. Clone the Repository

```bash
git clone https://github.com/tvdvoorde/phpauth.git
cd phpauth
```

#### 2. Configure PHP

Ensure PHP has the required extensions:

```bash
php -m | grep curl
php -m | grep json
php -m | grep openssl
```

#### 3. Running Locally

Since this application relies heavily on Azure App Service authentication, true local development requires either:

**Option A: Mock Authentication (Development)**

Create a development version of the code that mocks authentication:

```php
<?php
// dev-config.php
define('DEV_MODE', true);
define('MOCK_TOKEN', 'mock-access-token');
define('MOCK_USER', [
    'name' => 'Test User',
    'email' => 'test@example.com'
]);
?>
```

**Option B: Use Tunneling (ngrok)**

Expose your local server to the internet and configure it as a redirect URI:

```bash
# Install ngrok
# https://ngrok.com/download

# Start local PHP server
php -S localhost:8000

# Create tunnel
ngrok http 8000
```

Then update your Azure AD app registration redirect URI to include the ngrok URL.

**Option C: Deploy to Azure for Testing**

The recommended approach is to deploy to Azure for testing since the authentication is tightly integrated with Azure App Service.

### Running Backend Locally

```bash
cd back
php -S localhost:8001
```

Test the backend:
```bash
curl http://localhost:8001/index.php
```

### Running Frontend Locally

```bash
cd front
php -S localhost:8000
```

Open browser: `http://localhost:8000/index.php`

**Note**: Local execution will not have Azure AD authentication, so you'll need to mock the authentication headers or use Option B/C above.

## Project Structure

```
phpauth/
├── back/                   # Backend API
│   └── index.php          # Main API endpoint
├── front/                  # Frontend application
│   ├── index.php          # Main page
│   └── process.php        # Command processor
├── docs/                   # Documentation
│   ├── ARCHITECTURE.md    # Architecture overview
│   ├── API.md            # API reference
│   ├── DEVELOPMENT.md    # This file
│   ├── SETUP.md          # Setup guide
│   └── TROUBLESHOOTING.md # Troubleshooting
├── auth.json              # Sample auth configuration
└── README.md              # Project overview
```

## Development Workflow

### 1. Make Changes

Edit the PHP files in `front/` or `back/` directories.

### 2. Test Locally (if possible)

```bash
# For backend
cd back && php -S localhost:8001

# For frontend
cd front && php -S localhost:8000
```

### 3. Deploy to Azure

```bash
# Backend
rm -f back.zip
zip -j back.zip back/*.*
az webapp deployment source config-zip \
  --resource-group phpauth \
  --name <backend-name> \
  --src back.zip

# Frontend
rm -f front.zip
zip -j front.zip front/*.*
az webapp deployment source config-zip \
  --resource-group phpauth \
  --name <frontend-name> \
  --src front.zip
```

### 4. Test on Azure

Navigate to your deployed applications and test the changes.

### 5. Review Logs

```bash
# Stream logs
az webapp log tail --resource-group phpauth --name <app-name>
```

## Code Style Guidelines

### PHP Coding Standards

Follow PSR-12 coding standards:

1. **Indentation**: Use 4 spaces (no tabs)
2. **Line Length**: Maximum 120 characters
3. **Opening Braces**: Place on the same line for functions and classes
4. **Closing PHP Tag**: Omit `?>` at the end of files containing only PHP

### Example:

```php
<?php
class Example
{
    public function processRequest($data)
    {
        if (empty($data)) {
            return null;
        }
        
        return $this->formatResponse($data);
    }
}
```

### Security Best Practices

1. **Never hardcode secrets**:
   ```php
   // BAD
   $secret = "my-secret-key";
   
   // GOOD
   $secret = getenv('CLIENT_SECRET');
   ```

2. **Validate and sanitize input**:
   ```php
   $input = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
   ```

3. **Use prepared statements** (if using database):
   ```php
   $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
   $stmt->execute([$userId]);
   ```

4. **Avoid exposing sensitive data**:
   ```php
   // Don't echo full tokens in production
   error_reporting(0); // Disable in production
   ```

## Testing

### Manual Testing

1. **Backend API Test**:
   ```bash
   TOKEN="your-access-token"
   curl -H "Authorization: Bearer ${TOKEN}" \
     https://<backend-name>.azurewebsites.net
   ```

2. **Frontend Test**:
   - Navigate to frontend URL
   - Login with Azure AD
   - Verify all sections load correctly:
     - Debug information
     - Environment variables
     - Headers
     - Token display
     - Backend API call
     - Graph token acquisition
     - Graph API call

3. **On-Behalf-Of Flow Test**:
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

### Automated Testing

While this project doesn't currently have automated tests, here's how you could add them:

#### Unit Tests (PHPUnit)

```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Create test directory
mkdir tests
```

Example test file (`tests/BackendTest.php`):
```php
<?php
use PHPUnit\Framework\TestCase;

class BackendTest extends TestCase
{
    public function testApiResponse()
    {
        // Test implementation
        $this->assertTrue(true);
    }
}
```

#### Integration Tests

Create integration tests that verify:
- Authentication flow
- Token acquisition
- API calls
- OBO flow

## Debugging

### Enable PHP Error Reporting

Add to the top of your PHP files during development:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-errors.log');
?>
```

### Debug Authentication

Add debugging output to inspect headers and tokens:

```php
<?php
// Log all headers
foreach (getallheaders() as $name => $value) {
    error_log("Header: $name = $value");
}

// Log token claims (decode JWT)
function decodeJWT($token) {
    $parts = explode('.', $token);
    if (count($parts) === 3) {
        $payload = json_decode(base64_decode($parts[1]), true);
        return $payload;
    }
    return null;
}

$token = $_SERVER['HTTP_X_MS_TOKEN_AAD_ACCESS_TOKEN'] ?? '';
$claims = decodeJWT($token);
error_log("Token claims: " . print_r($claims, true));
?>
```

### Debug cURL Requests

Enable verbose output for cURL requests:

```php
<?php
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_VERBOSE, true);
$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$result = curl_exec($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
error_log("cURL verbose: " . $verboseLog);
?>
```

### Use Xdebug

For advanced debugging, install and configure Xdebug:

```bash
# Install Xdebug
pecl install xdebug

# Add to php.ini
zend_extension=xdebug.so
xdebug.mode=debug
xdebug.start_with_request=yes
```

## Environment Variables

### Frontend Environment Variables

The frontend can use these environment variables:

- `BACKEND_URL`: URL of the backend API
- `TENANT_ID`: Azure AD tenant ID
- `BACKEND_APP_ID`: Backend application client ID
- `BACKEND_CLIENT_SECRET`: Backend client secret (for OBO flow)

### Backend Environment Variables

The backend typically doesn't need custom environment variables as it uses App Service authentication.

### Setting Environment Variables Locally

```bash
# Linux/Mac
export BACKEND_URL="https://thx1140back.azurewebsites.net"

# Windows
set BACKEND_URL=https://thx1140back.azurewebsites.net
```

### Setting Environment Variables on Azure

```bash
az webapp config appsettings set \
  --resource-group phpauth \
  --name <app-name> \
  --settings BACKEND_URL="https://thx1140back.azurewebsites.net"
```

## Continuous Integration/Deployment

### GitHub Actions Example

Create `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Azure

on:
  push:
    branches: [ main ]

jobs:
  deploy-backend:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Zip backend
      run: |
        cd back
        zip -r ../back.zip .
    
    - name: Azure Login
      uses: azure/login@v1
      with:
        creds: ${{ secrets.AZURE_CREDENTIALS }}
    
    - name: Deploy to Azure Web App
      uses: azure/webapps-deploy@v2
      with:
        app-name: thx1140back
        package: back.zip

  deploy-frontend:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Zip frontend
      run: |
        cd front
        zip -r ../front.zip .
    
    - name: Azure Login
      uses: azure/login@v1
      with:
        creds: ${{ secrets.AZURE_CREDENTIALS }}
    
    - name: Deploy to Azure Web App
      uses: azure/webapps-deploy@v2
      with:
        app-name: thx1140front
        package: front.zip
```

## Performance Optimization

### Enable OPcache

Add to `php.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

### Cache Tokens

Implement token caching to reduce authentication calls:

```php
<?php
function getCachedToken($key) {
    $cacheFile = sys_get_temp_dir() . '/' . md5($key) . '.token';
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (time() < $data['expires']) {
            return $data['token'];
        }
    }
    return null;
}

function cacheToken($key, $token, $expiresIn) {
    $cacheFile = sys_get_temp_dir() . '/' . md5($key) . '.token';
    $data = [
        'token' => $token,
        'expires' => time() + $expiresIn - 300 // 5 min buffer
    ];
    file_put_contents($cacheFile, json_encode($data));
}
?>
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [Azure App Service PHP Documentation](https://docs.microsoft.com/azure/app-service/)
- [Azure AD OAuth 2.0 Documentation](https://docs.microsoft.com/azure/active-directory/develop/)
- [Microsoft Graph Documentation](https://docs.microsoft.com/graph/)

## Getting Help

If you encounter issues during development:

1. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Review Azure App Service logs
3. Check Application Insights (if configured)
4. Search [Stack Overflow](https://stackoverflow.com/questions/tagged/azure-active-directory)
5. Open an issue on GitHub
