<?php
/**
 * SECURITY WARNING: This endpoint has been disabled for security reasons.
 * 
 * The previous implementation allowed arbitrary command execution, which is
 * a critical security vulnerability. This endpoint should only be used in
 * secure development/debug environments with proper authentication and 
 * authorization controls.
 * 
 * For production use, implement:
 * 1. Strict authentication and authorization
 * 2. Command whitelisting (not blacklisting)
 * 3. Input validation and sanitization
 * 4. Proper logging and monitoring
 * 5. Rate limiting
 */

// Disabled for security - return error message
http_response_code(403);
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
</head>
<body>
    <h1>403 - Access Denied</h1>
    <p>This endpoint has been disabled for security reasons.</p>
    <p>Command execution via web interface poses significant security risks and should not be used in production.</p>
</body>
</html>

