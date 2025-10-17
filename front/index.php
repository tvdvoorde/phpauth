<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Debug page</title>
</head>
<body style="background-color: #FFFFFF;">
  <?php
    // Security: Properly escape output to prevent XSS
    function safe_echo($value) {
        echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    
    // Build request URL safely
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https" : "http";
    $requestPageUrl = $protocol . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
    
    // Get public IP with error handling
    $pubIp = @file_get_contents('https://api.ipify.org');
    if ($pubIp === false) {
        $pubIp = 'Unable to retrieve';
    }
  ?>
  <h3>Command Interface (Disabled for Security)</h3>
  <p style="color: red;"><strong>Note:</strong> Command execution has been disabled for security reasons. See process.php for details.</p>
  
<h3>Debug information</h3>
<br><b>Request Url: </b><?php safe_echo($requestPageUrl); ?>
<br><b>Outbound public IP: </b><?php safe_echo($pubIp); ?>
<br><b>Inbound client IP: </b><?php safe_echo($_SERVER['REMOTE_ADDR']); ?>
<br><b>Hostname: </b><?php safe_echo(gethostname()); ?>
<br><b>OS: </b><?php safe_echo(php_uname()); ?>
<h3>Environment variables (Filtered)</h3>
<?php
    // Security: Filter sensitive environment variables
    $output = null;
    $retval = null;
    exec("/bin/bash -c printenv", $output, $retval);
    
    // Define patterns for sensitive variables to filter
    $sensitive_patterns = [
        '/SECRET/i',
        '/PASSWORD/i',
        '/KEY/i',
        '/TOKEN/i',
        '/CREDENTIAL/i',
        '/CONNECTION/i'
    ];
    
    foreach ($output as $line) {
        $should_filter = false;
        foreach ($sensitive_patterns as $pattern) {
            if (preg_match($pattern, $line)) {
                $should_filter = true;
                break;
            }
        }
        
        if ($should_filter) {
            // Show variable name but hide value
            $parts = explode('=', $line, 2);
            echo "<br>" . htmlspecialchars($parts[0], ENT_QUOTES, 'UTF-8') . "=[FILTERED]";
        } else {
            echo "<br>" . htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
        }
    }
?>
<h3>Headers (Filtered)</h3>
    <?php
      $access_token = null;
      
      // Security: Filter sensitive headers
      $sensitive_headers = [
          'X-MS-TOKEN-AAD-ACCESS-TOKEN',
          'X-MS-TOKEN-AAD-ID-TOKEN',
          'X-MS-TOKEN-AAD-REFRESH-TOKEN',
          'Authorization',
          'Cookie',
          'X-MS-CLIENT-PRINCIPAL'
      ];
      
      foreach (getallheaders() as $name => $value) {
        if ($name == 'X-MS-TOKEN-AAD-ACCESS-TOKEN') {
          $access_token = $value;
        }
        
        // Check if this is a sensitive header
        if (in_array($name, $sensitive_headers)) {
            echo "<br><b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ": </b>[FILTERED]";
        } else {
            echo "<br><b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ": </b>" . htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
      }
    ?>

<h3>TOKEN (Not Displayed for Security)</h3>
<p><em>Access token is available but not displayed for security reasons.</em></p>

<h3>API</h3>
<?php
if ($access_token) {
    $url = "https://thx1140back.azurewebsites.net";
    $ch = curl_init($url);
    
    if ($ch === false) {
        echo "<br><span style='color: red;'>Error: Failed to initialize cURL</span>";
    } else {
        $request_headers = array();
        $request_headers[] = 'Accept: application/json';
        $request_headers[] = 'Authorization: Bearer '. $access_token; 
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Security: Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Security: Verify SSL host
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Security: Add timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Security: Disable redirects
        
        $result = curl_exec($ch);
        
        if ($result === false) {
            echo "<br><span style='color: red;'>Error: " . htmlspecialchars(curl_error($ch), ENT_QUOTES, 'UTF-8') . "</span>";
        } else {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            echo "<br>Result (HTTP " . intval($http_code) . ")<br><br>";
            
            // Safely display JSON response
            $json = json_decode($result, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                echo "<pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . "</pre>";
            } else {
                echo "<pre>" . htmlspecialchars($result, ENT_QUOTES, 'UTF-8') . "</pre>";
            }
        }
        
        curl_close($ch);
    }
} else {
    echo "<br><span style='color: orange;'>Warning: No access token available</span>";
}
?>

<h3>Graph Access Token</h3>
<?php
// Security: Client secret should be stored in environment variables or Azure Key Vault
// NEVER hardcode secrets in source code
$client_id = getenv('GRAPH_CLIENT_ID');
$client_secret = getenv('GRAPH_CLIENT_SECRET');
$tenant = getenv('GRAPH_TENANT');

if (empty($client_id) || empty($client_secret) || empty($tenant)) {
    echo "<br><span style='color: orange;'>Warning: Graph API credentials not configured in environment variables.</span>";
    echo "<br>Please set GRAPH_CLIENT_ID, GRAPH_CLIENT_SECRET, and GRAPH_TENANT environment variables.";
    $bearertoken = null;
} elseif (!$access_token) {
    echo "<br><span style='color: orange;'>Warning: No access token available for OBO flow.</span>";
    $bearertoken = null;
} else {
    $ch = curl_init();
    if ($ch === false) {
        echo "<br><span style='color: red;'>Error: Failed to initialize cURL</span>";
        $bearertoken = null;
    } else {
        $token_url = "https://login.microsoftonline.com/" . urlencode($tenant) . "/oauth2/v2.0/token";
        curl_setopt($ch, CURLOPT_URL, $token_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
            "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer"
            ."&client_id=".urlencode($client_id)
            ."&client_secret=".urlencode($client_secret)
            ."&assertion=".urlencode($access_token)
            ."&requested_token_use=on_behalf_of"
            ."&scope=".urlencode("openid profile email"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Security: Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Security: Verify SSL host
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Security: Add timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Security: Disable redirects
        
        $server_output = curl_exec($ch);
        
        if ($server_output === false) {
            echo "<br><span style='color: red;'>Error: " . htmlspecialchars(curl_error($ch), ENT_QUOTES, 'UTF-8') . "</span>";
            $bearertoken = null;
        } else {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $jsonoutput = json_decode($server_output, true);
            
            if ($http_code === 200 && isset($jsonoutput['access_token'])) {
                $bearertoken = $jsonoutput['access_token'];
                echo "<br><span style='color: green;'>Successfully obtained Graph API token (not displayed for security)</span>";
            } else {
                echo "<br><span style='color: red;'>Error: Failed to obtain token (HTTP " . intval($http_code) . ")</span>";
                if (isset($jsonoutput['error_description'])) {
                    echo "<br><span style='color: red;'>" . htmlspecialchars($jsonoutput['error_description'], ENT_QUOTES, 'UTF-8') . "</span>";
                }
                $bearertoken = null;
            }
        }
        
        curl_close($ch);
    }
}
?>

<h3>GRAPH</h3>
<?php
if ($bearertoken) {
    $url = "https://graph.microsoft.com/oidc/userinfo";
    $ch = curl_init($url);
    
    if ($ch === false) {
        echo "<br><span style='color: red;'>Error: Failed to initialize cURL</span>";
    } else {
        $request_headers = array();
        $request_headers[] = 'Accept: application/json';
        $request_headers[] = 'Authorization: Bearer '. $bearertoken; 
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Security: Enable SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Security: Verify SSL host
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Security: Add timeout
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Security: Disable redirects
        
        $result = curl_exec($ch);
        
        if ($result === false) {
            echo "<br><span style='color: red;'>Error: " . htmlspecialchars(curl_error($ch), ENT_QUOTES, 'UTF-8') . "</span>";
        } else {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($http_code === 200) {
                $json = json_decode($result, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    echo "<br><pre>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') . "</pre>";
                } else {
                    echo "<br><pre>" . htmlspecialchars($result, ENT_QUOTES, 'UTF-8') . "</pre>";
                }
            } else {
                echo "<br><span style='color: red;'>Error: HTTP " . intval($http_code) . "</span>";
                echo "<br><pre>" . htmlspecialchars($result, ENT_QUOTES, 'UTF-8') . "</pre>";
            }
        }
        
        curl_close($ch);
    }
} else {
    echo "<br><span style='color: orange;'>Warning: No Graph API bearer token available</span>";
}
?>
</body>
</html>