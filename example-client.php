<?php
/**
 * Example OAuth2 Client Application (PHP)
 * 
 * This demonstrates how to integrate with the test OAuth2 server
 * 
 * SETUP:
 * 1. Copy this file to your web root (e.g., C:\laragon\www\oauth-demo\)
 * 2. Update $OAUTH_CONFIG with your OAuth server URL
 * 3. Visit in browser: http://localhost/oauth-demo/example-client.php
 * 4. Click "Login with OAuth2"
 * 
 * REQUIREMENTS:
 * - PHP 7.4+
 * - cURL extension enabled
 */

session_start();

// OAuth2 Configuration
$OAUTH_CONFIG = [
    'authorizationEndpoint' => 'http://localhost/test-oauth/oauth/authorize',
    'tokenEndpoint' => 'http://localhost/test-oauth/oauth/token',
    'userInfoEndpoint' => 'http://localhost/test-oauth/oauth/userinfo',
    'clientId' => 'test-client',
    'clientSecret' => 'test-secret',
    'redirectUri' => 'http://localhost/oauth-demo/example-client.php',
    'scope' => 'openid profile email'
];

// Handle OAuth callback
if (isset($_GET['code'])) {
    handleOAuthCallback($OAUTH_CONFIG);
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Handle login initiation
if (isset($_GET['login'])) {
    initiateOAuthLogin($OAUTH_CONFIG);
    exit;
}

// Show home page
showHomePage($OAUTH_CONFIG);

function initiateOAuthLogin($config) {
    // Generate state for CSRF protection
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    
    // Build authorization URL
    $params = [
        'client_id' => $config['clientId'],
        'redirect_uri' => $config['redirectUri'],
        'response_type' => 'code',
        'state' => $state,
        'scope' => $config['scope']
    ];
    
    $authUrl = $config['authorizationEndpoint'] . '?' . http_build_query($params);
    
    // Redirect to OAuth2 server
    header('Location: ' . $authUrl);
}

function handleOAuthCallback($config) {
    $code = $_GET['code'] ?? '';
    $state = $_GET['state'] ?? '';
    $error = $_GET['error'] ?? '';
    
    // Handle errors
    if ($error) {
        die('<h1>❌ OAuth2 Error</h1><p>Error: ' . htmlspecialchars($error) . '</p><p>Description: ' . htmlspecialchars($_GET['error_description'] ?? 'Unknown error') . '</p><a href="?">Back to home</a>');
    }
    
    // Validate state (CSRF protection)
    if (!isset($_SESSION['oauth_state']) || $_SESSION['oauth_state'] !== $state) {
        die('Invalid state parameter - possible CSRF attack');
    }
    
    try {
        // Exchange authorization code for tokens
        $tokenData = exchangeCodeForToken($config, $code);
        
        if (!isset($tokenData['access_token'])) {
            throw new Exception('No access token received');
        }
        
        $accessToken = $tokenData['access_token'];
        $refreshToken = $tokenData['refresh_token'] ?? null;
        
        // Get user information
        $userInfo = getUserInfo($config, $accessToken);
        
        // Store in session
        $_SESSION['access_token'] = $accessToken;
        $_SESSION['refresh_token'] = $refreshToken;
        $_SESSION['user'] = $userInfo;
        
        // Clean up
        unset($_SESSION['oauth_state']);
        
        // Redirect to home page
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        
    } catch (Exception $e) {
        die('<h1>❌ Authentication Failed</h1><p>Error: ' . htmlspecialchars($e->getMessage()) . '</p><a href="?">Back to home</a>');
    }
}

function exchangeCodeForToken($config, $code) {
    $ch = curl_init($config['tokenEndpoint']);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $config['redirectUri'],
        'client_id' => $config['clientId'],
        'client_secret' => $config['clientSecret']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        throw new Exception($errorData['error_description'] ?? 'Token exchange failed');
    }
    
    return json_decode($response, true);
}

function getUserInfo($config, $accessToken) {
    $ch = curl_init($config['userInfoEndpoint']);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        throw new Exception($errorData['error_description'] ?? 'Failed to get user info');
    }
    
    return json_decode($response, true);
}

function showHomePage($config) {
    $isLoggedIn = isset($_SESSION['user']);
    $user = $_SESSION['user'] ?? null;
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>OAuth2 Example Client (PHP)</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            h1 {
                color: #333;
            }
            .login-btn {
                display: inline-block;
                padding: 12px 24px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                font-weight: bold;
            }
            .login-btn:hover {
                background-color: #0056b3;
            }
            .user-info {
                background-color: #e7f3ff;
                padding: 20px;
                border-radius: 4px;
                margin-top: 20px;
            }
            .logout-btn {
                display: inline-block;
                padding: 8px 16px;
                background-color: #dc3545;
                color: white;
                text-decoration: none;
                border-radius: 4px;
                margin-top: 10px;
            }
            pre {
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
            }
            .config {
                background-color: #fff3cd;
                padding: 15px;
                border-radius: 4px;
                margin-top: 20px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 OAuth2 Example Client (PHP)</h1>
            
            <?php if ($isLoggedIn): ?>
                <div class="user-info">
                    <h2>✅ Logged In</h2>
                    <p><strong>User Information:</strong></p>
                    <pre><?php echo htmlspecialchars(json_encode($user, JSON_PRETTY_PRINT)); ?></pre>
                    <p><strong>Access Token:</strong> <?php echo htmlspecialchars(substr($_SESSION['access_token'], 0, 20)); ?>...</p>
                    <a href="?logout" class="logout-btn">Logout</a>
                </div>
            <?php else: ?>
                <p>Welcome! This is an example OAuth2 client application built with PHP.</p>
                <p>Click the button below to login using the test OAuth2 server.</p>
                <a href="?login" class="login-btn">Login with OAuth2</a>
                
                <div class="config">
                    <strong>Configuration:</strong><br>
                    OAuth Server: <?php echo htmlspecialchars($config['authorizationEndpoint']); ?><br>
                    Client ID: <?php echo htmlspecialchars($config['clientId']); ?><br>
                    Redirect URI: <?php echo htmlspecialchars($config['redirectUri']); ?>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
