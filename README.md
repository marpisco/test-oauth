# 🔐 Test OAuth2 Server (PHP)

A simple, lightweight OAuth2 authorization server designed for testing and development purposes. Built in **pure PHP** with **zero dependencies**, making it perfect for use with **Laragon** or any PHP environment. This server implements the OAuth2 Authorization Code Flow and can be used as a replacement for services like Authentik or Microsoft Identity Platform during development.

## Features

- ✅ **Pure PHP** - No external dependencies required
- ✅ **OAuth2 Authorization Code Flow** implementation
- ✅ **OpenID Connect** compatible endpoints
- ✅ **Token Management**: Access tokens, refresh tokens, and authorization codes
- ✅ **Token Introspection** (RFC 7662) and **Revocation** (RFC 7009)
- ✅ **Discovery Endpoints** for OAuth2 and OpenID Connect
- ✅ **Pre-configured test users and clients** for immediate testing
- ✅ **Simple web-based login interface**
- ✅ **File-based storage** - no database required
- ✅ **Easy to customize** via `config.php`
- ✅ **Laragon compatible** - works out of the box
- ✅ **Clean URLs** with .htaccess

## Quick Start

### Option 1: Laragon (Recommended for Windows)

1. **Download and install [Laragon](https://laragon.org/)**

2. **Clone or copy this repository to Laragon's www directory:**
```bash
# Laragon default path
C:\laragon\www\test-oauth
```

3. **Start Laragon** (Apache + PHP)

4. **Access the server:**
   - Open your browser and navigate to: `http://localhost/test-oauth`
   - Or use Laragon's virtual host: `http://test-oauth.test` (if configured)

5. **Done!** The server is ready to use.

### Option 2: Standard PHP Installation

1. **Requirements:**
   - PHP 7.4 or higher
   - Apache with mod_rewrite enabled (or Nginx with proper configuration)

2. **Clone this repository:**
```bash
git clone <repository-url>
cd test-oauth
```

3. **Configure your web server** to point to the repository directory

4. **Ensure storage directory is writable:**
```bash
chmod 755 storage
```

5. **Access the server** through your web browser

### Verify Installation

Open your browser and navigate to:
```
http://localhost/test-oauth
```

You should see the server information page with all available endpoints and test credentials.

## Laragon Setup (Detailed)

### Step-by-Step Guide for Laragon Users

1. **Install Laragon:**
   - Download from [laragon.org](https://laragon.org/)
   - Install and start Laragon
   - Make sure Apache and PHP are running (green checkmarks)

2. **Add the OAuth2 server:**
   ```
   Copy this folder to: C:\laragon\www\test-oauth
   ```

3. **Access the server:**
   - Open browser: `http://localhost/test-oauth`
   - The homepage will display all endpoints and test credentials

4. **Optional - Create a virtual host:**
   - Right-click Laragon tray icon
   - Go to "Apache" → "sites-enabled"
   - Create a new virtual host pointing to the test-oauth directory
   - Access via: `http://test-oauth.test`

### Laragon Features

- ✅ **Auto-start** - Server runs when Laragon starts
- ✅ **SSL Support** - Easy HTTPS configuration
- ✅ **Pretty URLs** - Automatic virtual hosts
- ✅ **Multiple PHP versions** - Switch PHP versions easily

## Usage

### Basic OAuth2 Flow

1. **Direct your application to the authorization endpoint:**
```
http://localhost/test-oauth/oauth/authorize?client_id=test-client&redirect_uri=http://localhost:8080/callback&response_type=code&state=xyz
```

2. **User will be redirected to login page** where they can authenticate with test credentials

3. **After successful login**, user is redirected back to your application with an authorization code:
```
http://localhost:8080/callback?code=AUTHORIZATION_CODE&state=xyz
```

4. **Exchange the authorization code for tokens:**
```bash
curl -X POST http://localhost/test-oauth/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=authorization_code" \
  -d "code=AUTHORIZATION_CODE" \
  -d "redirect_uri=http://localhost:8080/callback" \
  -d "client_id=test-client" \
  -d "client_secret=test-secret"
```

Response:
```json
{
  "access_token": "...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "...",
  "scope": ""
}
```

5. **Use the access token to get user information:**
```bash
curl http://localhost/test-oauth/oauth/userinfo \
  -H "Authorization: Bearer ACCESS_TOKEN"
```

Response:
```json
{
  "sub": "1",
  "id": "1",
  "username": "testuser",
  "email": "testuser@example.com",
  "name": "Test User",
  "firstName": "Test",
  "lastName": "User"
}
```

## Pre-configured Test Data

### Test Clients

| Client ID | Client Secret | Redirect URIs |
|-----------|--------------|---------------|
| `test-client` | `test-secret` | Multiple localhost ports |
| `demo-app` | `demo-secret` | localhost:4200, localhost:5000 |
| `laragon-app` | `laragon-secret` | Laragon-friendly URIs |

**All redirect URIs support:**
- `http://localhost/*`
- `http://127.0.0.1/*`
- `http://*.local/*` (Laragon virtual hosts)
- `http://*.test/*` (Alternative virtual hosts)

### Test Users

| Username | Password | Email | Name |
|----------|----------|-------|------|
| `testuser` | `password` | testuser@example.com | Test User |
| `demo` | `demo` | demo@example.com | Demo User |
| `admin` | `admin` | admin@example.com | Admin User |
| `laragon` | `laragon` | laragon@example.com | Laragon User |

## Available Endpoints

All endpoints work with both root path and subdirectory installations.

### OAuth2 Core Endpoints

- **`GET /oauth/authorize`** - Authorization endpoint (redirects to login)
- **`POST /oauth/token`** - Token endpoint (exchange code for tokens)
- **`GET /oauth/userinfo`** - UserInfo endpoint (get user details)

### Additional Endpoints

- **`POST /oauth/introspect`** - Token introspection (RFC 7662)
- **`POST /oauth/revoke`** - Token revocation (RFC 7009)
- **`GET /.well-known/oauth-authorization-server`** - OAuth2 discovery
- **`GET /.well-known/openid-configuration`** - OpenID Connect discovery
- **`GET /health`** - Health check endpoint
- **`GET /`** - Server information page

### Internal Endpoints

- **`GET /login`** - Login page (user authentication)
- **`POST /authorize`** - Process login and issue authorization code

## Configuration

Edit `config.php` to customize:

- **Token lifetimes** (authorization code, access token, refresh token)
- **Add custom clients** with their redirect URIs
- **Add custom test users** with credentials
- **Configure scopes and grants**

Example configuration in `config.php`:
```php
<?php
$config = [
    'authorizationCodeLifetime' => 600,      // 10 minutes
    'accessTokenLifetime' => 3600,           // 1 hour
    'refreshTokenLifetime' => 86400,         // 24 hours
    
    'clients' => [
        [
            'clientId' => 'my-app',
            'clientSecret' => 'my-secret',
            'redirectUris' => ['http://localhost:9000/callback'],
            'grants' => ['authorization_code', 'refresh_token']
        ]
    ],
    
    'users' => [
        [
            'id' => '1',
            'username' => 'myuser',
            'password' => 'mypassword',
            'email' => 'user@example.com',
            'name' => 'My User'
        ]
    ]
];
```

### Storage

Tokens are stored in JSON format in the `storage/` directory:
- `storage/tokens.json` - Contains authorization codes, access tokens, and refresh tokens
- Expired tokens are automatically cleaned up on each request
- No database setup required

## Integration Examples

### Example 1: PHP/Laravel Application

```php
<?php
// Step 1: Redirect user to authorization endpoint
$authUrl = 'http://localhost/test-oauth/oauth/authorize?' . http_build_query([
    'client_id' => 'test-client',
    'redirect_uri' => 'http://localhost/myapp/callback',
    'response_type' => 'code',
    'state' => bin2hex(random_bytes(16))
]);

return redirect($authUrl);

// Step 2: Handle callback and exchange code for token
public function callback(Request $request)
{
    $code = $request->input('code');
    
    $response = Http::asForm()->post('http://localhost/test-oauth/oauth/token', [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => 'http://localhost/myapp/callback',
        'client_id' => 'test-client',
        'client_secret' => 'test-secret'
    ]);
    
    $accessToken = $response->json()['access_token'];
    
    // Step 3: Get user info
    $userResponse = Http::withToken($accessToken)
        ->get('http://localhost/test-oauth/oauth/userinfo');
    
    $user = $userResponse->json();
    // Handle user login...
}
```

### Example 2: Plain PHP Application

```php
<?php
session_start();

// Step 1: Redirect to authorization endpoint
if (!isset($_GET['code'])) {
    $authUrl = 'http://localhost/test-oauth/oauth/authorize?' . http_build_query([
        'client_id' => 'test-client',
        'redirect_uri' => 'http://localhost/myapp/callback.php',
        'response_type' => 'code',
        'state' => 'xyz123'
    ]);
    header('Location: ' . $authUrl);
    exit;
}

// Step 2: Exchange code for token
$code = $_GET['code'];

$ch = curl_init('http://localhost/test-oauth/oauth/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => 'http://localhost/myapp/callback.php',
    'client_id' => 'test-client',
    'client_secret' => 'test-secret'
]));

$response = curl_exec($ch);
$tokenData = json_decode($response, true);
$accessToken = $tokenData['access_token'];

// Step 3: Get user info
$ch = curl_init('http://localhost/test-oauth/oauth/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken
]);

$userResponse = curl_exec($ch);
$user = json_decode($userResponse, true);

echo "Welcome, " . $user['name'];
```

### Example 3: Using with Postman

1. Create a new request in Postman
2. Go to the **Authorization** tab
3. Select **OAuth 2.0** as the type
4. Configure:
   - **Grant Type**: Authorization Code
   - **Auth URL**: `http://localhost/test-oauth/oauth/authorize`
   - **Access Token URL**: `http://localhost/test-oauth/oauth/token`
   - **Client ID**: `test-client`
   - **Client Secret**: `test-secret`
   - **Scope**: (leave empty or add custom scopes)
   - **Callback URL**: (Postman will auto-configure)
5. Click **Get New Access Token**
6. Login with test credentials (`testuser` / `password`)
7. Use the token to make requests

### Example 4: WordPress Plugin

```php
<?php
// In your WordPress plugin or theme

add_action('init', function() {
    if (isset($_GET['oauth_login'])) {
        $authUrl = 'http://localhost/test-oauth/oauth/authorize?' . http_build_query([
            'client_id' => 'test-client',
            'redirect_uri' => home_url('/wp-admin/admin-ajax.php?action=oauth_callback'),
            'response_type' => 'code',
            'state' => wp_create_nonce('oauth_state')
        ]);
        wp_redirect($authUrl);
        exit;
    }
});

add_action('wp_ajax_nopriv_oauth_callback', 'handle_oauth_callback');
add_action('wp_ajax_oauth_callback', 'handle_oauth_callback');

function handle_oauth_callback() {
    $code = $_GET['code'] ?? '';
    
    $response = wp_remote_post('http://localhost/test-oauth/oauth/token', [
        'body' => [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => home_url('/wp-admin/admin-ajax.php?action=oauth_callback'),
            'client_id' => 'test-client',
            'client_secret' => 'test-secret'
        ]
    ]);
    
    $tokenData = json_decode(wp_remote_retrieve_body($response), true);
    $accessToken = $tokenData['access_token'];
    
    // Get user info and create/login WordPress user
    $userResponse = wp_remote_get('http://localhost/test-oauth/oauth/userinfo', [
        'headers' => ['Authorization' => 'Bearer ' . $accessToken]
    ]);
    
    $oauthUser = json_decode(wp_remote_retrieve_body($userResponse), true);
    
    // Create or get existing user
    $user = get_user_by('email', $oauthUser['email']);
    if (!$user) {
        $userId = wp_create_user($oauthUser['username'], wp_generate_password(), $oauthUser['email']);
        $user = get_user_by('id', $userId);
    }
    
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    
    wp_redirect(home_url());
    exit;
}
```

## Supported Grant Types

- ✅ **authorization_code** - Authorization Code Flow
- ✅ **refresh_token** - Refresh Token Flow

## Security Notes

⚠️ **This server is intended for TESTING and DEVELOPMENT only!**

- Passwords are stored in plain text
- No HTTPS support (use a reverse proxy if needed)
- Tokens are stored in memory (lost on restart)
- No rate limiting or security hardening
- No PKCE enforcement (though redirect URIs are validated)

**DO NOT USE IN PRODUCTION!**

## Troubleshooting

### Server not accessible
- **Laragon**: Make sure Apache is running (green checkmark in Laragon)
- **Standard setup**: Verify PHP and Apache/Nginx are running
- Check that mod_rewrite is enabled: `a2enmod rewrite` (Linux) or enable in httpd.conf
- Verify .htaccess file exists and is readable

### "404 Not Found" on endpoints
- Check that mod_rewrite is enabled in Apache
- Verify .htaccess file is being read (check Apache config: `AllowOverride All`)
- For Nginx, you need custom rewrite rules (see Nginx Configuration below)

### Authorization fails
- Verify the client_id exists in `config.php`
- Verify the redirect_uri is registered for the client in `config.php`
- Check that redirect_uri in authorize and token requests match exactly
- Clear PHP sessions: delete files in PHP's session directory

### Token exchange fails
- Ensure you're using the correct client_id and client_secret
- Verify the authorization code hasn't expired (10 minutes by default)
- Make sure the redirect_uri matches the one used in authorization request
- Check that storage directory is writable: `chmod 755 storage`

### Cannot access userinfo
- Verify the access token is valid and not expired
- Include the token in the Authorization header: `Bearer YOUR_TOKEN`
- Check that the Authorization header is being passed by your web server

### Storage errors
- Ensure the `storage/` directory exists and is writable
- Check file permissions: `chmod 755 storage`
- Verify PHP has write access to the directory

### Nginx Configuration

If using Nginx instead of Apache, add this to your site configuration:

```nginx
location /test-oauth {
    try_files $uri $uri/ /test-oauth/index.php?$query_string;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## License

MIT

## Contributing

Feel free to submit issues or pull requests to improve this test server!
