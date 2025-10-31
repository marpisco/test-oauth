const express = require('express');
const bodyParser = require('body-parser');
const { v4: uuidv4 } = require('uuid');
const config = require('./config');

const app = express();

// Middleware
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// In-memory storage for authorization codes, tokens, and sessions
const authorizationCodes = new Map();
const accessTokens = new Map();
const refreshTokens = new Map();

// Helper functions
function findClient(clientId) {
  return config.clients.find(c => c.clientId === clientId);
}

function findUser(username, password) {
  return config.users.find(u => u.username === username && u.password === password);
}

function validateRedirectUri(client, redirectUri) {
  return client.redirectUris.includes(redirectUri);
}

function generateCode() {
  return uuidv4();
}

function generateToken() {
  return uuidv4();
}

// Serve a simple login page
app.get('/login', (req, res) => {
  const { client_id, redirect_uri, state, response_type, scope } = req.query;
  
  res.send(`
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
      </style>
    </head>
    <body>
      <div class="login-container">
        <h1>Test OAuth2 Login</h1>
        <form action="/authorize" method="POST">
          <input type="hidden" name="client_id" value="${client_id || ''}">
          <input type="hidden" name="redirect_uri" value="${redirect_uri || ''}">
          <input type="hidden" name="state" value="${state || ''}">
          <input type="hidden" name="response_type" value="${response_type || ''}">
          <input type="hidden" name="scope" value="${scope || ''}">
          
          <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
          </div>
          
          <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
          </div>
          
          <button type="submit">Login & Authorize</button>
        </form>
        
        <div class="credentials">
          <strong>Test Credentials:</strong>
          • testuser / password<br>
          • demo / demo<br>
          • admin / admin
        </div>
        
        <div class="info">
          <strong>OAuth2 Flow Info:</strong>
          Client ID: ${client_id || 'N/A'}<br>
          Redirect URI: ${redirect_uri || 'N/A'}
        </div>
      </div>
    </body>
    </html>
  `);
});

// Authorization endpoint
app.get('/oauth/authorize', (req, res) => {
  const { client_id, redirect_uri, response_type, state, scope } = req.query;
  
  // Validate required parameters
  if (!client_id || !redirect_uri || !response_type) {
    return res.status(400).json({
      error: 'invalid_request',
      error_description: 'Missing required parameters'
    });
  }
  
  // Validate client
  const client = findClient(client_id);
  if (!client) {
    return res.status(400).json({
      error: 'invalid_client',
      error_description: 'Client not found'
    });
  }
  
  // Validate redirect URI
  if (!validateRedirectUri(client, redirect_uri)) {
    return res.status(400).json({
      error: 'invalid_request',
      error_description: 'Invalid redirect_uri'
    });
  }
  
  // Validate response type
  if (response_type !== 'code') {
    return res.status(400).json({
      error: 'unsupported_response_type',
      error_description: 'Only authorization_code flow is supported'
    });
  }
  
  // Redirect to login page
  res.redirect(`/login?client_id=${client_id}&redirect_uri=${encodeURIComponent(redirect_uri)}&state=${state || ''}&response_type=${response_type}&scope=${scope || ''}`);
});

// Handle login and authorization
app.post('/authorize', (req, res) => {
  const { username, password, client_id, redirect_uri, state, response_type, scope } = req.body;
  
  // Validate user credentials
  const user = findUser(username, password);
  if (!user) {
    return res.status(401).send(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Login Failed</title>
        <style>
          body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
          .error { background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; }
          a { color: #007bff; }
        </style>
      </head>
      <body>
        <div class="error">
          <h2>Login Failed</h2>
          <p>Invalid username or password.</p>
          <a href="/login?client_id=${client_id}&redirect_uri=${encodeURIComponent(redirect_uri)}&state=${state || ''}&response_type=${response_type}&scope=${scope || ''}">Try again</a>
        </div>
      </body>
      </html>
    `);
  }
  
  // Generate authorization code
  const code = generateCode();
  authorizationCodes.set(code, {
    clientId: client_id,
    redirectUri: redirect_uri,
    userId: user.id,
    scope: scope || '',
    expiresAt: Date.now() + (config.authorizationCodeLifetime * 1000)
  });
  
  // Redirect back to client with authorization code
  const separator = redirect_uri.includes('?') ? '&' : '?';
  const redirectUrl = `${redirect_uri}${separator}code=${code}${state ? `&state=${state}` : ''}`;
  res.redirect(redirectUrl);
});

// Token endpoint
app.post('/oauth/token', (req, res) => {
  const { grant_type, code, redirect_uri, client_id, client_secret, refresh_token } = req.body;
  
  // Validate client credentials
  const client = findClient(client_id);
  if (!client || client.clientSecret !== client_secret) {
    return res.status(401).json({
      error: 'invalid_client',
      error_description: 'Invalid client credentials'
    });
  }
  
  if (grant_type === 'authorization_code') {
    // Validate authorization code
    const codeData = authorizationCodes.get(code);
    if (!codeData) {
      return res.status(400).json({
        error: 'invalid_grant',
        error_description: 'Invalid authorization code'
      });
    }
    
    // Check if code is expired
    if (Date.now() > codeData.expiresAt) {
      authorizationCodes.delete(code);
      return res.status(400).json({
        error: 'invalid_grant',
        error_description: 'Authorization code expired'
      });
    }
    
    // Validate redirect URI and client ID
    if (codeData.clientId !== client_id || codeData.redirectUri !== redirect_uri) {
      return res.status(400).json({
        error: 'invalid_grant',
        error_description: 'Invalid redirect_uri or client_id'
      });
    }
    
    // Generate tokens
    const accessToken = generateToken();
    const newRefreshToken = generateToken();
    
    const user = config.users.find(u => u.id === codeData.userId);
    
    accessTokens.set(accessToken, {
      userId: codeData.userId,
      clientId: client_id,
      scope: codeData.scope,
      expiresAt: Date.now() + (config.accessTokenLifetime * 1000)
    });
    
    refreshTokens.set(newRefreshToken, {
      userId: codeData.userId,
      clientId: client_id,
      scope: codeData.scope,
      expiresAt: Date.now() + (config.refreshTokenLifetime * 1000)
    });
    
    // Delete used authorization code
    authorizationCodes.delete(code);
    
    return res.json({
      access_token: accessToken,
      token_type: 'Bearer',
      expires_in: config.accessTokenLifetime,
      refresh_token: newRefreshToken,
      scope: codeData.scope
    });
  } else if (grant_type === 'refresh_token') {
    // Validate refresh token
    const tokenData = refreshTokens.get(refresh_token);
    if (!tokenData) {
      return res.status(400).json({
        error: 'invalid_grant',
        error_description: 'Invalid refresh token'
      });
    }
    
    // Check if token is expired
    if (Date.now() > tokenData.expiresAt) {
      refreshTokens.delete(refresh_token);
      return res.status(400).json({
        error: 'invalid_grant',
        error_description: 'Refresh token expired'
      });
    }
    
    // Validate client ID
    if (tokenData.clientId !== client_id) {
      return res.status(400).json({
        error: 'invalid_grant',
        error_description: 'Invalid client_id'
      });
    }
    
    // Generate new access token
    const accessToken = generateToken();
    
    accessTokens.set(accessToken, {
      userId: tokenData.userId,
      clientId: client_id,
      scope: tokenData.scope,
      expiresAt: Date.now() + (config.accessTokenLifetime * 1000)
    });
    
    return res.json({
      access_token: accessToken,
      token_type: 'Bearer',
      expires_in: config.accessTokenLifetime,
      scope: tokenData.scope
    });
  } else {
    return res.status(400).json({
      error: 'unsupported_grant_type',
      error_description: 'Grant type not supported'
    });
  }
});

// UserInfo endpoint (OpenID Connect compatible)
app.get('/oauth/userinfo', (req, res) => {
  const authHeader = req.headers.authorization;
  
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({
      error: 'invalid_token',
      error_description: 'Missing or invalid authorization header'
    });
  }
  
  const token = authHeader.substring(7);
  const tokenData = accessTokens.get(token);
  
  if (!tokenData) {
    return res.status(401).json({
      error: 'invalid_token',
      error_description: 'Invalid access token'
    });
  }
  
  // Check if token is expired
  if (Date.now() > tokenData.expiresAt) {
    accessTokens.delete(token);
    return res.status(401).json({
      error: 'invalid_token',
      error_description: 'Access token expired'
    });
  }
  
  // Get user info
  const user = config.users.find(u => u.id === tokenData.userId);
  if (!user) {
    return res.status(404).json({
      error: 'user_not_found',
      error_description: 'User not found'
    });
  }
  
  // Return user info (excluding password)
  const { password, ...userInfo } = user;
  res.json({
    sub: user.id,
    ...userInfo
  });
});

// Token introspection endpoint (RFC 7662)
app.post('/oauth/introspect', (req, res) => {
  const { token, token_type_hint } = req.body;
  
  if (!token) {
    return res.status(400).json({
      error: 'invalid_request',
      error_description: 'Token parameter is required'
    });
  }
  
  // Check access tokens
  const tokenData = accessTokens.get(token);
  
  if (!tokenData) {
    return res.json({ active: false });
  }
  
  // Check if token is expired
  if (Date.now() > tokenData.expiresAt) {
    accessTokens.delete(token);
    return res.json({ active: false });
  }
  
  // Return token info
  const user = config.users.find(u => u.id === tokenData.userId);
  res.json({
    active: true,
    scope: tokenData.scope,
    client_id: tokenData.clientId,
    username: user ? user.username : undefined,
    token_type: 'Bearer',
    exp: Math.floor(tokenData.expiresAt / 1000),
    sub: tokenData.userId
  });
});

// Token revocation endpoint (RFC 7009)
app.post('/oauth/revoke', (req, res) => {
  const { token, token_type_hint } = req.body;
  
  if (!token) {
    return res.status(400).json({
      error: 'invalid_request',
      error_description: 'Token parameter is required'
    });
  }
  
  // Try to revoke the token
  let revoked = false;
  
  if (accessTokens.has(token)) {
    accessTokens.delete(token);
    revoked = true;
  }
  
  if (refreshTokens.has(token)) {
    refreshTokens.delete(token);
    revoked = true;
  }
  
  // RFC 7009 specifies that the response should always be 200 OK
  res.status(200).send();
});

// Discovery endpoint (OpenID Connect Discovery)
app.get('/.well-known/oauth-authorization-server', (req, res) => {
  const baseUrl = `http://${config.host}:${config.port}`;
  res.json({
    issuer: baseUrl,
    authorization_endpoint: `${baseUrl}/oauth/authorize`,
    token_endpoint: `${baseUrl}/oauth/token`,
    userinfo_endpoint: `${baseUrl}/oauth/userinfo`,
    introspection_endpoint: `${baseUrl}/oauth/introspect`,
    revocation_endpoint: `${baseUrl}/oauth/revoke`,
    response_types_supported: ['code'],
    grant_types_supported: ['authorization_code', 'refresh_token'],
    token_endpoint_auth_methods_supported: ['client_secret_post', 'client_secret_basic'],
    scopes_supported: ['openid', 'profile', 'email']
  });
});

// OpenID Connect Discovery endpoint
app.get('/.well-known/openid-configuration', (req, res) => {
  const baseUrl = `http://${config.host}:${config.port}`;
  res.json({
    issuer: baseUrl,
    authorization_endpoint: `${baseUrl}/oauth/authorize`,
    token_endpoint: `${baseUrl}/oauth/token`,
    userinfo_endpoint: `${baseUrl}/oauth/userinfo`,
    introspection_endpoint: `${baseUrl}/oauth/introspect`,
    revocation_endpoint: `${baseUrl}/oauth/revoke`,
    response_types_supported: ['code'],
    subject_types_supported: ['public'],
    id_token_signing_alg_values_supported: ['none'],
    scopes_supported: ['openid', 'profile', 'email'],
    token_endpoint_auth_methods_supported: ['client_secret_post', 'client_secret_basic'],
    claims_supported: ['sub', 'name', 'email', 'username']
  });
});

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Root endpoint with server info
app.get('/', (req, res) => {
  const baseUrl = `http://${config.host}:${config.port}`;
  res.send(`
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
      </style>
    </head>
    <body>
      <div class="container">
        <h1>🔐 Test OAuth2 Server</h1>
        <p><span class="status">RUNNING</span></p>
        <p>This is a simple OAuth2 test server for development and testing purposes.</p>
        
        <h2>OAuth2 Endpoints</h2>
        <div class="endpoint">
          <strong>Authorization:</strong> <code>${baseUrl}/oauth/authorize</code>
        </div>
        <div class="endpoint">
          <strong>Token:</strong> <code>${baseUrl}/oauth/token</code>
        </div>
        <div class="endpoint">
          <strong>UserInfo:</strong> <code>${baseUrl}/oauth/userinfo</code>
        </div>
        <div class="endpoint">
          <strong>Introspection:</strong> <code>${baseUrl}/oauth/introspect</code>
        </div>
        <div class="endpoint">
          <strong>Revocation:</strong> <code>${baseUrl}/oauth/revoke</code>
        </div>
        <div class="endpoint">
          <strong>Discovery:</strong> <code>${baseUrl}/.well-known/oauth-authorization-server</code>
        </div>
        
        <h2>Test Credentials</h2>
        <ul>
          <li><strong>Client ID:</strong> <code>test-client</code> | <strong>Secret:</strong> <code>test-secret</code></li>
          <li><strong>Client ID:</strong> <code>demo-app</code> | <strong>Secret:</strong> <code>demo-secret</code></li>
        </ul>
        
        <h2>Test Users</h2>
        <ul>
          <li><code>testuser</code> / <code>password</code></li>
          <li><code>demo</code> / <code>demo</code></li>
          <li><code>admin</code> / <code>admin</code></li>
        </ul>
        
        <h2>Quick Start</h2>
        <p>To test the OAuth2 flow, direct your application to:</p>
        <div class="endpoint">
          <code>${baseUrl}/oauth/authorize?client_id=test-client&redirect_uri=YOUR_CALLBACK_URL&response_type=code&state=xyz</code>
        </div>
      </div>
    </body>
    </html>
  `);
});

// Start server
const server = app.listen(config.port, config.host, () => {
  console.log('╔════════════════════════════════════════════════════════════╗');
  console.log('║          Test OAuth2 Server Started Successfully          ║');
  console.log('╚════════════════════════════════════════════════════════════╝');
  console.log('');
  console.log(`🚀 Server running at: http://${config.host}:${config.port}`);
  console.log('');
  console.log('📋 OAuth2 Endpoints:');
  console.log(`   • Authorization: http://${config.host}:${config.port}/oauth/authorize`);
  console.log(`   • Token:         http://${config.host}:${config.port}/oauth/token`);
  console.log(`   • UserInfo:      http://${config.host}:${config.port}/oauth/userinfo`);
  console.log(`   • Discovery:     http://${config.host}:${config.port}/.well-known/oauth-authorization-server`);
  console.log('');
  console.log('🔑 Test Clients:');
  config.clients.forEach(client => {
    console.log(`   • ${client.clientId} / ${client.clientSecret}`);
  });
  console.log('');
  console.log('👤 Test Users:');
  config.users.forEach(user => {
    console.log(`   • ${user.username} / ${user.password}`);
  });
  console.log('');
  console.log('✨ Ready to accept OAuth2 requests!');
  console.log('');
});

// Graceful shutdown
process.on('SIGTERM', () => {
  console.log('SIGTERM received, shutting down gracefully...');
  server.close(() => {
    console.log('Server closed');
    process.exit(0);
  });
});
