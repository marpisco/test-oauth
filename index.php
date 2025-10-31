<?php
/**
 * Test OAuth2 Server - Main Entry Point
 * A simple OAuth2 authorization server for testing and development
 * Compatible with Laragon and standard PHP environments
 */

session_start();

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/oauth-server.php';

$oauth = new OAuth2Server();

// Get the request path
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);

// Remove script directory from path
if ($scriptName !== '/') {
    $path = str_replace($scriptName, '', $requestUri);
} else {
    $path = $requestUri;
}

// Ensure path starts with /
if (empty($path) || $path[0] !== '/') {
    $path = '/' . $path;
}

// Route the request
switch ($path) {
    case '/':
    case '/index.php':
        showHomePage();
        break;
    
    case '/oauth/authorize':
        $oauth->handleAuthorize();
        break;
    
    case '/login':
        $oauth->showLoginPage();
        break;
    
    case '/authorize':
        $oauth->handleLogin();
        break;
    
    case '/oauth/token':
        $oauth->handleToken();
        break;
    
    case '/oauth/userinfo':
        $oauth->handleUserInfo();
        break;
    
    case '/oauth/introspect':
        $oauth->handleIntrospect();
        break;
    
    case '/oauth/revoke':
        $oauth->handleRevoke();
        break;
    
    case '/.well-known/oauth-authorization-server':
        $oauth->handleDiscovery();
        break;
    
    case '/.well-known/openid-configuration':
        $oauth->handleOpenIDDiscovery();
        break;
    
    case '/health':
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'ok',
            'timestamp' => date('c')
        ]);
        break;
    
    default:
        http_response_code(404);
        echo '404 - Not Found';
        break;
}

function showHomePage() {
    global $config;
    $baseUrl = getBaseUrl();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Test OAuth2 Server</title>
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
                margin-top: 0;
            }
            h2 {
                color: #555;
                border-bottom: 2px solid #007bff;
                padding-bottom: 10px;
            }
            .endpoint {
                background-color: #f8f9fa;
                padding: 15px;
                margin: 10px 0;
                border-radius: 4px;
                border-left: 4px solid #007bff;
            }
            code {
                background-color: #e9ecef;
                padding: 2px 6px;
                border-radius: 3px;
                font-family: monospace;
            }
            .status {
                display: inline-block;
                padding: 5px 10px;
                background-color: #28a745;
                color: white;
                border-radius: 4px;
                font-weight: bold;
            }
            ul {
                line-height: 1.8;
            }
            .php-info {
                background-color: #d1ecf1;
                padding: 10px;
                border-radius: 4px;
                margin-top: 20px;
                color: #0c5460;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔐 Test OAuth2 Server (PHP)</h1>
            <p><span class="status">RUNNING</span></p>
            <p>This is a simple OAuth2 test server for development and testing purposes.</p>
            
            <div class="php-info">
                <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
            </div>
            
            <h2>OAuth2 Endpoints</h2>
            <div class="endpoint">
                <strong>Authorization:</strong> <code><?php echo htmlspecialchars($baseUrl); ?>/oauth/authorize</code>
            </div>
            <div class="endpoint">
                <strong>Token:</strong> <code><?php echo htmlspecialchars($baseUrl); ?>/oauth/token</code>
            </div>
            <div class="endpoint">
                <strong>UserInfo:</strong> <code><?php echo htmlspecialchars($baseUrl); ?>/oauth/userinfo</code>
            </div>
            <div class="endpoint">
                <strong>Introspection:</strong> <code><?php echo htmlspecialchars($baseUrl); ?>/oauth/introspect</code>
            </div>
            <div class="endpoint">
                <strong>Revocation:</strong> <code><?php echo htmlspecialchars($baseUrl); ?>/oauth/revoke</code>
            </div>
            <div class="endpoint">
                <strong>Discovery:</strong> <code><?php echo htmlspecialchars($baseUrl); ?>/.well-known/oauth-authorization-server</code>
            </div>
            
            <h2>Test Credentials</h2>
            <ul>
                <?php foreach ($config['clients'] as $client): ?>
                <li><strong>Client ID:</strong> <code><?php echo htmlspecialchars($client['clientId']); ?></code> | 
                    <strong>Secret:</strong> <code><?php echo htmlspecialchars($client['clientSecret']); ?></code></li>
                <?php endforeach; ?>
            </ul>
            
            <h2>Test Users</h2>
            <ul>
                <?php foreach ($config['users'] as $user): ?>
                <li><code><?php echo htmlspecialchars($user['username']); ?></code> / 
                    <code><?php echo htmlspecialchars($user['password']); ?></code></li>
                <?php endforeach; ?>
            </ul>
            
            <h2>Quick Start</h2>
            <p>To test the OAuth2 flow, direct your application to:</p>
            <div class="endpoint">
                <code><?php echo htmlspecialchars($baseUrl); ?>/oauth/authorize?client_id=test-client&amp;redirect_uri=YOUR_CALLBACK_URL&amp;response_type=code&amp;state=xyz</code>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' 
                 || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . $host . ($script !== '/' ? $script : '');
}
