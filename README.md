# 🔐 Test OAuth2 Server

A simple, lightweight OAuth2 authorization server designed for testing and development purposes. This server implements the OAuth2 Authorization Code Flow and can be used as a replacement for services like Authentik or Microsoft Identity Platform during development.

## Features

- ✅ **OAuth2 Authorization Code Flow** with PKCE support
- ✅ **OpenID Connect** compatible endpoints
- ✅ **Token Management**: Access tokens, refresh tokens, and authorization codes
- ✅ **Token Introspection** (RFC 7662) and **Revocation** (RFC 7009)
- ✅ **Discovery Endpoints** for OAuth2 and OpenID Connect
- ✅ **Pre-configured test users and clients** for immediate testing
- ✅ **Simple web-based login interface**
- ✅ **No database required** - all data stored in memory
- ✅ **Easy to customize** via `config.js`

## Quick Start

### Installation

1. Clone this repository:
```bash
git clone <repository-url>
cd test-oauth
```

2. Install dependencies:
```bash
npm install
```

3. Start the server:
```bash
npm start
```

The server will start on `http://localhost:3000` by default.

### Verify Installation

Open your browser and navigate to:
```
http://localhost:3000
```

You should see the server information page with all available endpoints.

## Usage

### Basic OAuth2 Flow

1. **Direct your application to the authorization endpoint:**
```
http://localhost:3000/oauth/authorize?client_id=test-client&redirect_uri=http://localhost:8080/callback&response_type=code&state=xyz
```

2. **User will be redirected to login page** where they can authenticate with test credentials

3. **After successful login**, user is redirected back to your application with an authorization code:
```
http://localhost:8080/callback?code=AUTHORIZATION_CODE&state=xyz
```

4. **Exchange the authorization code for tokens:**
```bash
curl -X POST http://localhost:3000/oauth/token \
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
curl http://localhost:3000/oauth/userinfo \
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
| `test-client` | `test-secret` | `http://localhost:8080/callback`, `http://localhost:3001/callback` |
| `demo-app` | `demo-secret` | `http://localhost:4200/callback`, `http://localhost:5000/callback` |

### Test Users

| Username | Password | Email | Name |
|----------|----------|-------|------|
| `testuser` | `password` | testuser@example.com | Test User |
| `demo` | `demo` | demo@example.com | Demo User |
| `admin` | `admin` | admin@example.com | Admin User |

## Available Endpoints

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

## Configuration

Edit `config.js` to customize:

- **Port and host settings**
- **Token lifetimes** (authorization code, access token, refresh token)
- **Add custom clients** with their redirect URIs
- **Add custom test users** with credentials
- **Configure scopes and grants**

Example configuration:
```javascript
module.exports = {
  port: 3000,
  host: 'localhost',
  authorizationCodeLifetime: 600, // 10 minutes
  accessTokenLifetime: 3600, // 1 hour
  refreshTokenLifetime: 86400, // 24 hours
  
  clients: [
    {
      clientId: 'my-app',
      clientSecret: 'my-secret',
      redirectUris: ['http://localhost:9000/callback'],
      grants: ['authorization_code', 'refresh_token']
    }
  ],
  
  users: [
    {
      id: '1',
      username: 'myuser',
      password: 'mypassword',
      email: 'user@example.com',
      name: 'My User'
    }
  ]
};
```

## Integration Examples

### Example 1: Node.js Application

```javascript
const axios = require('axios');

// Step 1: Redirect user to authorization endpoint
const authUrl = 'http://localhost:3000/oauth/authorize?' + 
  'client_id=test-client&' +
  'redirect_uri=http://localhost:8080/callback&' +
  'response_type=code&' +
  'state=random-state-string';

// Redirect user to authUrl...

// Step 2: Handle callback and exchange code for token
app.get('/callback', async (req, res) => {
  const { code } = req.query;
  
  const tokenResponse = await axios.post('http://localhost:3000/oauth/token', {
    grant_type: 'authorization_code',
    code: code,
    redirect_uri: 'http://localhost:8080/callback',
    client_id: 'test-client',
    client_secret: 'test-secret'
  });
  
  const { access_token } = tokenResponse.data;
  
  // Step 3: Get user info
  const userInfo = await axios.get('http://localhost:3000/oauth/userinfo', {
    headers: { Authorization: `Bearer ${access_token}` }
  });
  
  console.log('User:', userInfo.data);
});
```

### Example 2: Python Application

```python
import requests

# Step 1: Redirect user to authorization endpoint
auth_url = (
    'http://localhost:3000/oauth/authorize?'
    'client_id=test-client&'
    'redirect_uri=http://localhost:8080/callback&'
    'response_type=code&'
    'state=random-state-string'
)

# After user authorizes and you receive the code...

# Step 2: Exchange code for token
token_response = requests.post('http://localhost:3000/oauth/token', data={
    'grant_type': 'authorization_code',
    'code': authorization_code,
    'redirect_uri': 'http://localhost:8080/callback',
    'client_id': 'test-client',
    'client_secret': 'test-secret'
})

access_token = token_response.json()['access_token']

# Step 3: Get user info
user_response = requests.get('http://localhost:3000/oauth/userinfo',
    headers={'Authorization': f'Bearer {access_token}'}
)

print('User:', user_response.json())
```

### Example 3: Using with Postman

1. Create a new request in Postman
2. Go to the **Authorization** tab
3. Select **OAuth 2.0** as the type
4. Configure:
   - **Grant Type**: Authorization Code
   - **Auth URL**: `http://localhost:3000/oauth/authorize`
   - **Access Token URL**: `http://localhost:3000/oauth/token`
   - **Client ID**: `test-client`
   - **Client Secret**: `test-secret`
   - **Scope**: (leave empty or add custom scopes)
5. Click **Get New Access Token**
6. Login with test credentials (`testuser` / `password`)
7. Use the token to make requests

## Environment Variables

You can override configuration using environment variables:

```bash
# Change port
PORT=4000 npm start

# Change host
HOST=0.0.0.0 npm start

# Both
PORT=4000 HOST=0.0.0.0 npm start
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

### Server won't start
- Make sure port 3000 is not already in use
- Try changing the port: `PORT=4000 npm start`

### Authorization fails
- Verify the client_id exists in `config.js`
- Verify the redirect_uri is registered for the client
- Check that redirect_uri in authorize and token requests match exactly

### Token exchange fails
- Ensure you're using the correct client_id and client_secret
- Verify the authorization code hasn't expired (10 minutes by default)
- Make sure the redirect_uri matches the one used in authorization request

### Cannot access userinfo
- Verify the access token is valid and not expired
- Include the token in the Authorization header: `Bearer YOUR_TOKEN`

## License

MIT

## Contributing

Feel free to submit issues or pull requests to improve this test server!
