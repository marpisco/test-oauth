# ⚡ Quick Start Guide

Get the Test OAuth2 Server running in under 3 minutes!

## For Laragon Users (Windows)

### 1. Copy Files
```
Copy this folder to: C:\laragon\www\test-oauth
```

### 2. Start Laragon
- Open Laragon
- Click "Start All"
- Wait for green checkmarks

### 3. Access Server
Open browser: **http://localhost/test-oauth**

✅ **Done!** You should see the OAuth2 server homepage.

---

## For Standard PHP/Apache Users

### 1. Requirements
- PHP 7.4 or higher
- Apache with mod_rewrite enabled

### 2. Install
```bash
git clone <this-repo> /var/www/html/test-oauth
cd /var/www/html/test-oauth
chmod 755 storage
```

### 3. Access
Open browser: **http://localhost/test-oauth**

---

## Testing the OAuth2 Flow

### Method 1: Use the Example Client

1. Copy `example-client.php` to another folder in your web root
2. Edit the OAuth2 configuration in the file
3. Access in browser: `http://localhost/your-folder/example-client.php`
4. Click "Login with OAuth2"
5. Login with: `testuser` / `password`

### Method 2: Use cURL

```bash
# Step 1: Get authorization URL
AUTH_URL="http://localhost/test-oauth/oauth/authorize?client_id=test-client&redirect_uri=http://localhost:8080/callback&response_type=code&state=xyz"
echo "Visit this URL in browser: $AUTH_URL"

# Step 2: After login, you'll get redirected with a code
# Step 3: Exchange code for token (replace YOUR_CODE with the actual code)
curl -X POST http://localhost/test-oauth/oauth/token \
  -d "grant_type=authorization_code" \
  -d "code=YOUR_CODE" \
  -d "redirect_uri=http://localhost:8080/callback" \
  -d "client_id=test-client" \
  -d "client_secret=test-secret"

# Step 4: Use the access token (replace YOUR_TOKEN)
curl http://localhost/test-oauth/oauth/userinfo \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Method 3: Use Postman

1. Create new request → Authorization tab
2. Type: **OAuth 2.0**
3. Configure:
   - **Grant Type**: Authorization Code
   - **Auth URL**: `http://localhost/test-oauth/oauth/authorize`
   - **Access Token URL**: `http://localhost/test-oauth/oauth/token`
   - **Client ID**: `test-client`
   - **Client Secret**: `test-secret`
4. Click "Get New Access Token"
5. Login with: `testuser` / `password`

---

## Test Credentials

### Clients
- **Client ID**: `test-client` | **Secret**: `test-secret`
- **Client ID**: `laragon-app` | **Secret**: `laragon-secret`

### Users
- `testuser` / `password`
- `demo` / `demo`
- `admin` / `admin`
- `laragon` / `laragon`

---

## Common URLs

| Endpoint | URL |
|----------|-----|
| **Homepage** | http://localhost/test-oauth |
| **Authorization** | http://localhost/test-oauth/oauth/authorize |
| **Token** | http://localhost/test-oauth/oauth/token |
| **User Info** | http://localhost/test-oauth/oauth/userinfo |
| **Health Check** | http://localhost/test-oauth/health |

---

## Customize

Edit `config.php` to:
- Add your own clients
- Add your own test users
- Change token lifetimes
- Modify redirect URIs

---

## Troubleshooting

### "404 Not Found" on OAuth endpoints
- **Laragon**: Make sure Apache is running
- **Apache**: Enable mod_rewrite: `sudo a2enmod rewrite && sudo systemctl restart apache2`
- Check `.htaccess` file exists

### Can't write tokens
```bash
chmod 755 storage
```

### Server shows blank page
- Check PHP version: `php --version` (need 7.4+)
- Check Apache error log
- Verify `.htaccess` is being read

---

## Next Steps

- Read [README.md](README.md) for detailed documentation
- Check [LARAGON.md](LARAGON.md) for Laragon-specific setup
- Review [example-client.php](example-client.php) for integration examples
- Customize [config.php](config.php) for your needs

---

## Support

Having issues? Check:
1. PHP version (7.4+)
2. Apache is running
3. mod_rewrite is enabled
4. `.htaccess` exists and is readable
5. `storage/` directory exists and is writable

Still stuck? Check the detailed [README.md](README.md) for more troubleshooting tips.
