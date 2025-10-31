# 🚀 Laragon Setup Guide

Complete guide for setting up the Test OAuth2 Server with Laragon on Windows.

## What is Laragon?

Laragon is a portable, isolated, fast & powerful universal development environment for PHP, Node.js, Python, Java, Go, Ruby, etc. It's perfect for Windows developers and provides:

- ✅ Apache + PHP out of the box
- ✅ Automatic virtual hosts
- ✅ SSL support
- ✅ Easy PHP version switching
- ✅ Beautiful GUI

Download: [https://laragon.org/](https://laragon.org/)

## Quick Setup (3 Minutes)

### Step 1: Install Laragon

1. Download Laragon from [laragon.org](https://laragon.org/)
2. Run the installer (choose "Full" version for complete features)
3. Install to default location: `C:\laragon`
4. Launch Laragon

### Step 2: Install OAuth2 Server

**Option A: Clone with Git**
```bash
cd C:\laragon\www
git clone <repository-url> test-oauth
```

**Option B: Manual Copy**
1. Download/extract this repository
2. Copy the entire folder to: `C:\laragon\www\test-oauth`

### Step 3: Start Apache

1. Click "Start All" in Laragon
2. Wait for Apache and MySQL to show green checkmarks

### Step 4: Test the Server

Open your browser and navigate to:
```
http://localhost/test-oauth
```

You should see the OAuth2 server homepage with all endpoints and test credentials.

**🎉 That's it! Your OAuth2 server is ready!**

## Creating a Virtual Host

Laragon can automatically create pretty URLs like `http://test-oauth.test`

### Automatic Virtual Host

1. Right-click the **Laragon** tray icon
2. Go to **Apache** → **sites-enabled**
3. Select **Auto Virtual Hosts** (should already be enabled)
4. Create a symlink or copy the folder to: `C:\laragon\www\test-oauth`
5. Right-click Laragon → **Apache** → **Reload**

Now access via: `http://test-oauth.test`

### Manual Virtual Host

If you want a custom domain name:

1. Right-click Laragon tray icon
2. Go to **Apache** → **sites-enabled**
3. Create a new file: `test-oauth.conf`
4. Add this configuration:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/laragon/www/test-oauth"
    ServerName oauth-server.test
    ServerAlias *.oauth-server.test
    <Directory "C:/laragon/www/test-oauth">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

5. Edit `C:\Windows\System32\drivers\etc\hosts` (as Administrator)
6. Add this line:
```
127.0.0.1 oauth-server.test
```

7. Right-click Laragon → **Apache** → **Reload**

Now access via: `http://oauth-server.test`

## Using with Your Applications

### Example 1: Another Laragon Project

Let's say you have a project at `C:\laragon\www\myapp`:

**In myapp/oauth-test.php:**
```php
<?php
session_start();

// OAuth2 Configuration
$authUrl = 'http://localhost/test-oauth/oauth/authorize?' . http_build_query([
    'client_id' => 'laragon-app',
    'client_secret' => 'laragon-secret',
    'redirect_uri' => 'http://localhost/myapp/callback.php',
    'response_type' => 'code',
    'state' => bin2hex(random_bytes(16))
]);

echo '<a href="' . $authUrl . '">Login with OAuth2</a>';
```

**In myapp/callback.php:**
```php
<?php
session_start();

$code = $_GET['code'] ?? '';

// Exchange code for token
$ch = curl_init('http://localhost/test-oauth/oauth/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => 'http://localhost/myapp/callback.php',
    'client_id' => 'laragon-app',
    'client_secret' => 'laragon-secret'
]));

$response = curl_exec($ch);
$data = json_decode($response, true);
$accessToken = $data['access_token'];

// Get user info
$ch = curl_init('http://localhost/test-oauth/oauth/userinfo');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken
]);

$userResponse = curl_exec($ch);
$user = json_decode($userResponse, true);

echo '<pre>' . print_r($user, true) . '</pre>';
```

### Example 2: Laravel Application

If you have a Laravel project in Laragon:

**Install Socialite (optional, or use raw cURL):**
```bash
composer require laravel/socialite
```

**In your controller:**
```php
public function redirectToProvider()
{
    $query = http_build_query([
        'client_id' => 'laragon-app',
        'redirect_uri' => route('oauth.callback'),
        'response_type' => 'code',
        'state' => Str::random(40),
        'scope' => 'openid profile email',
    ]);

    return redirect('http://localhost/test-oauth/oauth/authorize?' . $query);
}

public function handleProviderCallback(Request $request)
{
    $code = $request->input('code');
    
    $response = Http::asForm()->post('http://localhost/test-oauth/oauth/token', [
        'grant_type' => 'authorization_code',
        'client_id' => 'laragon-app',
        'client_secret' => 'laragon-secret',
        'redirect_uri' => route('oauth.callback'),
        'code' => $code,
    ]);

    $data = $response->json();
    $accessToken = $data['access_token'];
    
    $userResponse = Http::withToken($accessToken)
        ->get('http://localhost/test-oauth/oauth/userinfo');
    
    $oauthUser = $userResponse->json();
    
    // Create or authenticate user...
    Auth::login($user);
    
    return redirect('/dashboard');
}
```

## Testing the Complete Flow

### Test with cURL (from Laragon Terminal)

Right-click Laragon → **Terminal** → **cmder**, then:

```bash
# Step 1: Get authorization code (will redirect to login page)
curl -L "http://localhost/test-oauth/oauth/authorize?client_id=test-client&redirect_uri=http://localhost:8080/callback&response_type=code"

# Step 2: Simulate login and get code
curl -X POST "http://localhost/test-oauth/authorize" \
  -d "username=laragon" \
  -d "password=laragon" \
  -d "client_id=test-client" \
  -d "redirect_uri=http://localhost:8080/callback" \
  -d "response_type=code" \
  -L -v

# Step 3: Extract code from redirect and exchange for token
curl -X POST "http://localhost/test-oauth/oauth/token" \
  -d "grant_type=authorization_code" \
  -d "code=YOUR_CODE_HERE" \
  -d "redirect_uri=http://localhost:8080/callback" \
  -d "client_id=test-client" \
  -d "client_secret=test-secret"

# Step 4: Use access token to get user info
curl "http://localhost/test-oauth/oauth/userinfo" \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

### Test with Postman

1. Open Postman
2. Create a new request
3. Go to **Authorization** tab
4. Select **OAuth 2.0**
5. Configure:
   - **Grant Type**: Authorization Code
   - **Auth URL**: `http://localhost/test-oauth/oauth/authorize`
   - **Access Token URL**: `http://localhost/test-oauth/oauth/token`
   - **Client ID**: `laragon-app`
   - **Client Secret**: `laragon-secret`
   - **Scope**: `openid profile email`
6. Click **Get New Access Token**
7. Browser opens → Login with `laragon` / `laragon`
8. Token appears in Postman → Click **Use Token**

## Customization

### Adding Your Own Client

Edit `C:\laragon\www\test-oauth\config.php`:

```php
'clients' => [
    // Add your client
    [
        'clientId' => 'myapp',
        'clientSecret' => 'mysecret',
        'redirectUris' => [
            'http://localhost/myapp/callback.php',
            'http://myapp.test/callback'
        ],
        'grants' => ['authorization_code', 'refresh_token']
    ],
    // ... existing clients
],
```

### Adding Your Own Test User

Edit `C:\laragon\www\test-oauth\config.php`:

```php
'users' => [
    // Add your user
    [
        'id' => '5',
        'username' => 'myuser',
        'password' => 'mypassword',
        'email' => 'myuser@example.com',
        'name' => 'My User',
        'firstName' => 'My',
        'lastName' => 'User'
    ],
    // ... existing users
],
```

## Troubleshooting

### OAuth server shows blank page
- Check Apache is running (green checkmark in Laragon)
- Check PHP version is 7.4+ (Laragon → PHP → Version)
- Check Apache error log: Right-click Laragon → Apache → apache_error.log

### "404 Not Found" on OAuth endpoints
- Verify `.htaccess` file exists in `C:\laragon\www\test-oauth`
- Enable mod_rewrite: Right-click Laragon → Apache → httpd.conf
- Find and uncomment: `LoadModule rewrite_module modules/mod_rewrite.so`
- Restart Apache

### Cannot write tokens
- Check folder permissions for `storage/` directory
- Run Laragon as Administrator if permission issues persist

### Virtual host not working
- Check `C:\Windows\System32\drivers\etc\hosts` has entry
- Restart Apache after making changes
- Try: Right-click Laragon → Quick Add → Add `test-oauth` to hosts file

## Advanced Configuration

### Enable HTTPS (SSL)

1. Right-click Laragon → **SSL** → **test-oauth.test**
2. Wait for certificate generation
3. Access via: `https://test-oauth.test`
4. Update your client redirect URIs to use HTTPS

### Multiple PHP Versions

Laragon supports multiple PHP versions:

1. Right-click Laragon → **PHP** → Download more versions
2. Switch version: Right-click → **PHP** → Select version
3. Restart Apache

### Database Support (Optional)

If you want to add MySQL storage:

1. Start MySQL in Laragon
2. Create database: `laragon_oauth`
3. Modify `includes/oauth-server.php` to use PDO instead of file storage

## Support

If you encounter issues:

1. Check Laragon logs: Right-click → Apache → error logs
2. Check PHP error log: Right-click → PHP → php_error.log
3. Verify file permissions in the test-oauth directory
4. Ensure mod_rewrite is enabled in Apache

## Next Steps

- Read the main [README.md](README.md) for API documentation
- Check [example-client.php](example-client.php) for integration examples
- Customize [config.php](config.php) for your needs
- Build your own OAuth2 client application!

## Resources

- [Laragon Documentation](https://laragon.org/docs/)
- [OAuth 2.0 Specification](https://oauth.net/2/)
- [PHP cURL Documentation](https://www.php.net/manual/en/book.curl.php)
