# Contributing to phpauth

Thank you for your interest in contributing to phpauth! This document provides guidelines and instructions for contributing to the project.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for everyone.

## How to Contribute

### Reporting Bugs

If you find a bug, please create an issue with:

1. **Clear title** - Brief description of the issue
2. **Description** - Detailed explanation of the problem
3. **Steps to reproduce** - Exact steps to reproduce the issue
4. **Expected behavior** - What you expected to happen
5. **Actual behavior** - What actually happened
6. **Environment** - Azure region, PHP version, browser, etc.
7. **Screenshots** - If applicable
8. **Logs** - Relevant error messages or logs

### Suggesting Features

Feature suggestions are welcome! Please create an issue with:

1. **Clear title** - Brief description of the feature
2. **Use case** - Why this feature would be useful
3. **Proposed solution** - How you envision it working
4. **Alternatives** - Other solutions you've considered

### Pull Requests

We welcome pull requests! Here's how to contribute code:

#### 1. Fork and Clone

```bash
# Fork the repository on GitHub, then:
git clone https://github.com/YOUR-USERNAME/phpauth.git
cd phpauth
git remote add upstream https://github.com/tvdvoorde/phpauth.git
```

#### 2. Create a Branch

```bash
git checkout -b feature/your-feature-name
# or
git checkout -b fix/your-bug-fix
```

Use descriptive branch names:
- `feature/` for new features
- `fix/` for bug fixes
- `docs/` for documentation changes
- `refactor/` for code refactoring

#### 3. Make Your Changes

Follow the coding standards and guidelines below.

#### 4. Test Your Changes

- Test locally if possible
- Deploy to Azure and test end-to-end
- Verify authentication flows work correctly
- Check logs for errors

#### 5. Commit Your Changes

```bash
git add .
git commit -m "Brief description of changes"
```

Write clear commit messages:
- Use present tense ("Add feature" not "Added feature")
- Be concise but descriptive
- Reference issue numbers if applicable (#123)

#### 6. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Then create a pull request on GitHub with:
- Clear title describing the change
- Description of what changed and why
- Reference to related issues
- Screenshots (if UI changes)
- Test results

## Coding Standards

### PHP Style Guide

Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards:

#### Indentation and Formatting

```php
<?php
// Use 4 spaces for indentation
class Example
{
    public function exampleMethod($param1, $param2)
    {
        if ($param1 === $param2) {
            return true;
        }
        
        return false;
    }
}
```

#### Naming Conventions

- **Classes**: PascalCase (`UserAuthentication`)
- **Methods**: camelCase (`getUserInfo()`)
- **Variables**: camelCase (`$accessToken`)
- **Constants**: UPPER_SNAKE_CASE (`MAX_RETRY_COUNT`)

#### File Structure

```php
<?php
// 1. PHP opening tag
// 2. Namespace declaration (if any)
// 3. Use statements
// 4. Class/function definitions
// 5. No closing PHP tag at end of file
```

### Security Best Practices

#### 1. Never Hardcode Secrets

```php
// ❌ BAD
$clientSecret = "abc123secret";

// ✅ GOOD
$clientSecret = getenv('BACKEND_CLIENT_SECRET');
```

#### 2. Validate and Sanitize Input

```php
// ❌ BAD
$command = $_POST['command'];
exec($command);

// ✅ GOOD
$command = filter_input(INPUT_POST, 'command', FILTER_SANITIZE_STRING);
if (preg_match('/^\/bin\/bash/', $command)) {
    exec($command, $output, $returnCode);
}
```

#### 3. Use HTTPS

```php
// Always use HTTPS URLs
$url = "https://api.example.com";
```

#### 4. Validate Tokens

```php
// Verify token audience, issuer, and expiration
function validateToken($token) {
    // Token validation logic
}
```

### Documentation Standards

#### Code Comments

Use comments to explain **why**, not **what**:

```php
// ❌ BAD - Obvious what the code does
// Set the URL
$url = "https://api.example.com";

// ✅ GOOD - Explains why
// Use the backend API URL instead of Graph API for server info
$url = "https://api.example.com";
```

#### Function Documentation

```php
/**
 * Exchange user token for Graph API token using On-Behalf-Of flow
 *
 * @param string $userToken The original user access token
 * @param string $clientId The backend application client ID
 * @param string $clientSecret The backend application secret
 * @return string|null Graph API access token or null on failure
 */
function getGraphToken($userToken, $clientId, $clientSecret)
{
    // Implementation
}
```

#### Markdown Documentation

- Use clear headings and sections
- Include code examples
- Add links to related documentation
- Keep lines under 120 characters
- Use tables for structured data
- Include command examples with expected output

### Git Workflow

#### Commit Messages

Format:
```
Type: Brief description (50 chars or less)

More detailed explanation if needed. Wrap at 72 characters.
Can include multiple paragraphs.

- Bullet points for lists
- Use present tense
- Reference issues: Fixes #123
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

Examples:
```
feat: Add token caching to reduce auth calls

Implements in-memory token cache with expiration checking.
Reduces authentication overhead by 80%.

Fixes #45
```

```
fix: Correct audience validation in backend API

The audience check was comparing client ID instead of
API identifier. Now validates against api://{client-id}.

Fixes #67
```

## Project-Specific Guidelines

### Azure Configuration

When adding new Azure resources or configuration:

1. Document in relevant docs files
2. Update SETUP.md with configuration steps
3. Update QUICKREF.md with commands
4. Test on fresh deployment

### Authentication Changes

When modifying authentication:

1. Test all authentication flows:
   - Initial login
   - Token refresh
   - Backend API access
   - On-Behalf-Of flow
   - Graph API access
2. Verify token claims
3. Check error handling
4. Update API.md documentation

### Documentation Changes

When updating documentation:

1. Check for broken links
2. Update table of contents if needed
3. Keep consistent formatting
4. Test code examples
5. Review for clarity

## Development Environment

### Required Tools

- Git
- Azure CLI
- PHP 7.4+
- Text editor (VS Code recommended)
- Azure subscription

### Recommended VS Code Extensions

- PHP Intelephense
- Azure Account
- Azure App Service
- GitLens
- Markdown All in One

### Testing Checklist

Before submitting a PR:

- [ ] Code follows PSR-12 standards
- [ ] No hardcoded secrets
- [ ] Changes tested on Azure
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
- [ ] Logs reviewed for errors
- [ ] Authentication flows verified
- [ ] API responses validated

## Review Process

### Pull Request Review

All pull requests will be reviewed for:

1. **Code quality** - Follows standards and best practices
2. **Security** - No security vulnerabilities
3. **Functionality** - Works as intended
4. **Documentation** - Changes are documented
5. **Testing** - Adequately tested

### Review Timeline

- Initial review: Within 1 week
- Follow-up reviews: Within 3 days
- Merge: After approval and CI passes

### Addressing Review Comments

- Respond to all review comments
- Make requested changes or discuss alternatives
- Update PR description if scope changes
- Request re-review when ready

## License

By contributing, you agree that your contributions will be licensed under the same license as the project.

## Questions?

If you have questions about contributing:

1. Check existing [documentation](README.md)
2. Search [existing issues](https://github.com/tvdvoorde/phpauth/issues)
3. Create a new issue with your question
4. Tag with `question` label

## Recognition

Contributors will be recognized in:
- GitHub contributors list
- Release notes
- Project documentation (for significant contributions)

Thank you for contributing to phpauth! 🎉
