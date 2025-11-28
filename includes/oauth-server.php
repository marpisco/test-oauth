<?php
/**
 * OAuth2 Server Implementation
 * 
 * This class handles all OAuth2 operations including:
 * - Authorization code flow
 * - Token generation and validation
 * - User authentication
 * - Token introspection and revocation
 */

class OAuth2Server {
    private $config;
    private $storageFile;
    
    public function __construct() {
        global $config;
        $this->config = $config;
        $this->storageFile = __DIR__ . '/../storage/tokens.json';
        $this->initStorage();
    }
    
    private function initStorage() {
        if (!file_exists($this->storageFile)) {
            $dir = dirname($this->storageFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->saveStorage([
                'authorizationCodes' => [],
                'accessTokens' => [],
                'refreshTokens' => []
            ]);
        }
    }
    
    private function loadStorage() {
        if (!file_exists($this->storageFile)) {
            return [
                'authorizationCodes' => [],
                'accessTokens' => [],
                'refreshTokens' => []
            ];
        }
        
        $content = file_get_contents($this->storageFile);
        $data = json_decode($content, true);
        
        // Clean expired entries
        $this->cleanExpiredTokens($data);
        
        return $data ?: [
            'authorizationCodes' => [],
            'accessTokens' => [],
            'refreshTokens' => []
        ];
    }
    
    private function saveStorage($data) {
        file_put_contents($this->storageFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    private function cleanExpiredTokens(&$data) {
        $now = time();
        
        // Clean authorization codes
        foreach ($data['authorizationCodes'] as $code => $codeData) {
            if ($codeData['expiresAt'] < $now) {
                unset($data['authorizationCodes'][$code]);
            }
        }
        
        // Clean access tokens
        foreach ($data['accessTokens'] as $token => $tokenData) {
            if ($tokenData['expiresAt'] < $now) {
                unset($data['accessTokens'][$token]);
            }
        }
        
        // Clean refresh tokens
        foreach ($data['refreshTokens'] as $token => $tokenData) {
            if ($tokenData['expiresAt'] < $now) {
                unset($data['refreshTokens'][$token]);
            }
        }
    }
    
    private function findClient($clientId) {
        foreach ($this->config['clients'] as $client) {
            if ($client['clientId'] === $clientId) {
                return $client;
            }
        }
        return null;
    }
    
    private function findUser($username, $password) {
        foreach ($this->config['users'] as $user) {
            if ($user['username'] === $username && $user['password'] === $password) {
                return $user;
            }
        }
        return null;
    }
    
    private function validateRedirectUri($client, $redirectUri) {
        return in_array($redirectUri, $client['redirectUris']);
    }
    
    private function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    public function handleAuthorize() {
        $clientId = $_GET['client_id'] ?? '';
        $redirectUri = $_GET['redirect_uri'] ?? '';
        $responseType = $_GET['response_type'] ?? '';
        $state = $_GET['state'] ?? '';
        $scope = $_GET['scope'] ?? '';
        
        // Validate required parameters
        if (empty($clientId) || empty($redirectUri) || empty($responseType)) {
            $this->jsonError('invalid_request', 'Missing required parameters', 400);
            return;
        }
        
        // Validate client
        $client = $this->findClient($clientId);
        if (!$client) {
            $this->jsonError('invalid_client', 'Client not found', 400);
            return;
        }
        
        // Validate redirect URI
        if (!$this->validateRedirectUri($client, $redirectUri)) {
            $this->jsonError('invalid_request', 'Invalid redirect_uri', 400);
            return;
        }
        
        // Validate response type
        if ($responseType !== 'code') {
            $this->jsonError('unsupported_response_type', 'Only authorization_code flow is supported', 400);
            return;
        }
        
        // Store request in session and redirect to login
        $_SESSION['oauth_request'] = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => $responseType,
            'scope' => $scope
        ];
        
        header('Location: ' . getBaseUrl() . '/login');
        exit;
    }
    
    public function showLoginPage() {
        if (!isset($_SESSION['oauth_request'])) {
            echo 'Invalid request. Please start the OAuth flow again.';
            return;
        }
        
        $request = $_SESSION['oauth_request'];
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Test OAuth2 Server - Login</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    max-width: 400px;
                    margin: 50px auto;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .login-container {
                    background: white;
                    padding: 30px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #333;
                    margin-top: 0;
                }
                .form-group {
                    margin-bottom: 15px;
                }
                label {
                    display: block;
                    margin-bottom: 5px;
                    color: #666;
                }
                input[type="text"], input[type="password"] {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-sizing: border-box;
                }
                button {
                    width: 100%;
                    padding: 10px;
                    background-color: #007bff;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                }
                button:hover {
                    background-color: #0056b3;
                }
                .info {
                    margin-top: 20px;
                    padding: 10px;
                    background-color: #e7f3ff;
                    border-left: 4px solid #007bff;
                    font-size: 14px;
                }
                .info strong {
                    display: block;
                    margin-bottom: 5px;
                }
                .credentials {
                    font-size: 12px;
                    color: #666;
                    margin-top: 15px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 4px;
                }
                .error {
                    background-color: #f8d7da;
                    color: #721c24;
                    padding: 10px;
                    border-radius: 4px;
                    margin-bottom: 15px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
                <h1>Test OAuth2 Login</h1>
                <?php if (isset($_SESSION['login_error'])): ?>
                <div class="error">
                    <?php echo htmlspecialchars($_SESSION['login_error']); ?>
                    <?php unset($_SESSION['login_error']); ?>
                </div>
                <?php endif; ?>
                <form action="<?php echo htmlspecialchars(getBaseUrl()); ?>/authorize" method="POST">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit">Login & Authorize</button>
                </form>
                
                <div class="credentials">
                    <strong>Test Credentials:</strong>
                    <?php foreach ($this->config['users'] as $user): ?>
                    • <?php echo htmlspecialchars($user['username']); ?> / <?php echo htmlspecialchars($user['password']); ?><br>
                    <?php endforeach; ?>
                </div>
                
                <div class="info">
                    <strong>OAuth2 Flow Info:</strong>
                    Client ID: <?php echo htmlspecialchars($request['client_id']); ?><br>
                    Redirect URI: <?php echo htmlspecialchars($request['redirect_uri']); ?>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
    
    public function handleLogin() {
        if (!isset($_SESSION['oauth_request'])) {
            echo 'Invalid request. Please start the OAuth flow again.';
            return;
        }
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $request = $_SESSION['oauth_request'];
        
        // Validate user credentials
        $user = $this->findUser($username, $password);
        if (!$user) {
            $_SESSION['login_error'] = 'Invalid username or password';
            header('Location: ' . getBaseUrl() . '/login');
            exit;
        }
        
        // Generate authorization code
        $code = $this->generateToken();
        $storage = $this->loadStorage();
        
        $storage['authorizationCodes'][$code] = [
            'clientId' => $request['client_id'],
            'redirectUri' => $request['redirect_uri'],
            'userId' => $user['id'],
            'scope' => $request['scope'],
            'expiresAt' => time() + $this->config['authorizationCodeLifetime']
        ];
        
        $this->saveStorage($storage);
        unset($_SESSION['oauth_request']);
        
        // Redirect back to client
        $separator = strpos($request['redirect_uri'], '?') !== false ? '&' : '?';
        $redirectUrl = $request['redirect_uri'] . $separator . 'code=' . $code;
        if (!empty($request['state'])) {
            $redirectUrl .= '&state=' . urlencode($request['state']);
        }
        
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    public function handleToken() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('invalid_request', 'Only POST method is allowed', 405);
            return;
        }
        
        $grantType = $_POST['grant_type'] ?? '';
        $code = $_POST['code'] ?? '';
        $redirectUri = $_POST['redirect_uri'] ?? '';
        $clientId = $_POST['client_id'] ?? '';
        $clientSecret = $_POST['client_secret'] ?? '';
        $refreshToken = $_POST['refresh_token'] ?? '';
        
        // Validate client credentials
        $client = $this->findClient($clientId);
        if (!$client || $client['clientSecret'] !== $clientSecret) {
            $this->jsonError('invalid_client', 'Invalid client credentials', 401);
            return;
        }
        
        if ($grantType === 'authorization_code') {
            $this->handleAuthorizationCodeGrant($code, $redirectUri, $clientId, $client);
        } elseif ($grantType === 'refresh_token') {
            $this->handleRefreshTokenGrant($refreshToken, $clientId);
        } else {
            $this->jsonError('unsupported_grant_type', 'Grant type not supported', 400);
        }
    }
    
    private function handleAuthorizationCodeGrant($code, $redirectUri, $clientId, $client) {
        $storage = $this->loadStorage();
        
        // Validate authorization code
        if (!isset($storage['authorizationCodes'][$code])) {
            $this->jsonError('invalid_grant', 'Invalid authorization code', 400);
            return;
        }
        
        $codeData = $storage['authorizationCodes'][$code];
        
        // Check if code is expired
        if (time() > $codeData['expiresAt']) {
            unset($storage['authorizationCodes'][$code]);
            $this->saveStorage($storage);
            $this->jsonError('invalid_grant', 'Authorization code expired', 400);
            return;
        }
        
        // Validate redirect URI and client ID
        if ($codeData['clientId'] !== $clientId || $codeData['redirectUri'] !== $redirectUri) {
            $this->jsonError('invalid_grant', 'Invalid redirect_uri or client_id', 400);
            return;
        }
        
        // Generate tokens
        $accessToken = $this->generateToken();
        $newRefreshToken = $this->generateToken();
        
        $storage['accessTokens'][$accessToken] = [
            'userId' => $codeData['userId'],
            'clientId' => $clientId,
            'scope' => $codeData['scope'],
            'expiresAt' => time() + $this->config['accessTokenLifetime']
        ];
        
        $storage['refreshTokens'][$newRefreshToken] = [
            'userId' => $codeData['userId'],
            'clientId' => $clientId,
            'scope' => $codeData['scope'],
            'expiresAt' => time() + $this->config['refreshTokenLifetime']
        ];
        
        // Delete used authorization code
        unset($storage['authorizationCodes'][$code]);
        $this->saveStorage($storage);
        
        header('Content-Type: application/json');
        echo json_encode([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->config['accessTokenLifetime'],
            'refresh_token' => $newRefreshToken,
            'scope' => $codeData['scope']
        ]);
    }
    
    private function handleRefreshTokenGrant($refreshToken, $clientId) {
        $storage = $this->loadStorage();
        
        // Validate refresh token
        if (!isset($storage['refreshTokens'][$refreshToken])) {
            $this->jsonError('invalid_grant', 'Invalid refresh token', 400);
            return;
        }
        
        $tokenData = $storage['refreshTokens'][$refreshToken];
        
        // Check if token is expired
        if (time() > $tokenData['expiresAt']) {
            unset($storage['refreshTokens'][$refreshToken]);
            $this->saveStorage($storage);
            $this->jsonError('invalid_grant', 'Refresh token expired', 400);
            return;
        }
        
        // Validate client ID
        if ($tokenData['clientId'] !== $clientId) {
            $this->jsonError('invalid_grant', 'Invalid client_id', 400);
            return;
        }
        
        // Generate new access token
        $accessToken = $this->generateToken();
        
        $storage['accessTokens'][$accessToken] = [
            'userId' => $tokenData['userId'],
            'clientId' => $clientId,
            'scope' => $tokenData['scope'],
            'expiresAt' => time() + $this->config['accessTokenLifetime']
        ];
        
        $this->saveStorage($storage);
        
        header('Content-Type: application/json');
        echo json_encode([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->config['accessTokenLifetime'],
            'scope' => $tokenData['scope']
        ]);
    }
    
    public function handleUserInfo() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
            $this->jsonError('invalid_token', 'Missing or invalid authorization header', 401);
            return;
        }
        
        $token = substr($authHeader, 7);
        $storage = $this->loadStorage();
        
        if (!isset($storage['accessTokens'][$token])) {
            $this->jsonError('invalid_token', 'Invalid access token', 401);
            return;
        }
        
        $tokenData = $storage['accessTokens'][$token];
        
        // Check if token is expired
        if (time() > $tokenData['expiresAt']) {
            unset($storage['accessTokens'][$token]);
            $this->saveStorage($storage);
            $this->jsonError('invalid_token', 'Access token expired', 401);
            return;
        }
        
        // Get user info
        $user = null;
        foreach ($this->config['users'] as $u) {
            if ($u['id'] === $tokenData['userId']) {
                $user = $u;
                break;
            }
        }
        
        if (!$user) {
            $this->jsonError('user_not_found', 'User not found', 404);
            return;
        }
        
        // Return user info (excluding password)
        unset($user['password']);
        $user['sub'] = $user['id'];
        
        header('Content-Type: application/json');
        echo json_encode($user);
    }
    
    public function handleIntrospect() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('invalid_request', 'Only POST method is allowed', 405);
            return;
        }
        
        $token = $_POST['token'] ?? '';
        
        if (empty($token)) {
            $this->jsonError('invalid_request', 'Token parameter is required', 400);
            return;
        }
        
        $storage = $this->loadStorage();
        
        // Check access tokens
        if (!isset($storage['accessTokens'][$token])) {
            header('Content-Type: application/json');
            echo json_encode(['active' => false]);
            return;
        }
        
        $tokenData = $storage['accessTokens'][$token];
        
        // Check if token is expired
        if (time() > $tokenData['expiresAt']) {
            unset($storage['accessTokens'][$token]);
            $this->saveStorage($storage);
            header('Content-Type: application/json');
            echo json_encode(['active' => false]);
            return;
        }
        
        // Find username
        $username = null;
        foreach ($this->config['users'] as $user) {
            if ($user['id'] === $tokenData['userId']) {
                $username = $user['username'];
                break;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'active' => true,
            'scope' => $tokenData['scope'],
            'client_id' => $tokenData['clientId'],
            'username' => $username,
            'token_type' => 'Bearer',
            'exp' => $tokenData['expiresAt'],
            'sub' => $tokenData['userId']
        ]);
    }
    
    public function handleRevoke() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('invalid_request', 'Only POST method is allowed', 405);
            return;
        }
        
        $token = $_POST['token'] ?? '';
        
        if (empty($token)) {
            $this->jsonError('invalid_request', 'Token parameter is required', 400);
            return;
        }
        
        $storage = $this->loadStorage();
        
        // Try to revoke the token
        if (isset($storage['accessTokens'][$token])) {
            unset($storage['accessTokens'][$token]);
        }
        
        if (isset($storage['refreshTokens'][$token])) {
            unset($storage['refreshTokens'][$token]);
        }
        
        $this->saveStorage($storage);
        
        // RFC 7009 specifies that the response should always be 200 OK
        http_response_code(200);
    }
    
    public function handleDiscovery() {
        $baseUrl = getBaseUrl();
        
        header('Content-Type: application/json');
        echo json_encode([
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl . '/oauth/authorize',
            'token_endpoint' => $baseUrl . '/oauth/token',
            'userinfo_endpoint' => $baseUrl . '/oauth/userinfo',
            'introspection_endpoint' => $baseUrl . '/oauth/introspect',
            'revocation_endpoint' => $baseUrl . '/oauth/revoke',
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
            'scopes_supported' => ['openid', 'profile', 'email']
        ]);
    }
    
    public function handleOpenIDDiscovery() {
        $baseUrl = getBaseUrl();
        
        header('Content-Type: application/json');
        echo json_encode([
            'issuer' => $baseUrl,
            'authorization_endpoint' => $baseUrl . '/oauth/authorize',
            'token_endpoint' => $baseUrl . '/oauth/token',
            'userinfo_endpoint' => $baseUrl . '/oauth/userinfo',
            'introspection_endpoint' => $baseUrl . '/oauth/introspect',
            'revocation_endpoint' => $baseUrl . '/oauth/revoke',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['none'],
            'scopes_supported' => ['openid', 'profile', 'email'],
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
            'claims_supported' => ['sub', 'name', 'email', 'username']
        ]);
    }
    
    private function jsonError($error, $description, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $error,
            'error_description' => $description
        ]);
    }
}
