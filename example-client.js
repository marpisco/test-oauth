/**
 * Example OAuth2 Client
 * 
 * This is a simple example showing how to integrate with the test OAuth2 server.
 * Run this alongside the OAuth2 server to see a complete working example.
 * 
 * Usage:
 *   1. Start the OAuth2 server: npm start
 *   2. In another terminal, run: node example-client.js
 *   3. Visit http://localhost:8080 in your browser
 *   4. Click "Login with OAuth2"
 *   5. You'll be redirected to the OAuth2 server login page
 *   6. Login with test credentials (testuser/password)
 *   7. You'll be redirected back with user information
 */

const express = require('express');
const axios = require('axios');

const app = express();
const PORT = 8080;

// OAuth2 Configuration
const OAUTH_CONFIG = {
  authorizationEndpoint: 'http://localhost:3000/oauth/authorize',
  tokenEndpoint: 'http://localhost:3000/oauth/token',
  userInfoEndpoint: 'http://localhost:3000/oauth/userinfo',
  clientId: 'test-client',
  clientSecret: 'test-secret',
  redirectUri: 'http://localhost:8080/callback',
  scope: 'openid profile email'
};

// In-memory session storage (use a proper session store in production)
const sessions = new Map();

// Home page
app.get('/', (req, res) => {
  const sessionId = req.query.session;
  const session = sessionId ? sessions.get(sessionId) : null;
  
  res.send(`
    <!DOCTYPE html>
    <html>
    <head>
      <title>OAuth2 Example Client</title>
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
      </style>
    </head>
    <body>
      <div class="container">
        <h1>🔐 OAuth2 Example Client</h1>
        ${session ? `
          <div class="user-info">
            <h2>✅ Logged In</h2>
            <p><strong>User Information:</strong></p>
            <pre>${JSON.stringify(session.user, null, 2)}</pre>
            <p><strong>Access Token:</strong> ${session.accessToken.substring(0, 20)}...</p>
            <a href="/logout?session=${sessionId}" class="logout-btn">Logout</a>
          </div>
        ` : `
          <p>Welcome! This is an example OAuth2 client application.</p>
          <p>Click the button below to login using the test OAuth2 server.</p>
          <a href="/login" class="login-btn">Login with OAuth2</a>
        `}
      </div>
    </body>
    </html>
  `);
});

// Initiate OAuth2 login
app.get('/login', (req, res) => {
  // Generate state for CSRF protection
  const state = Math.random().toString(36).substring(7);
  
  // Store state in session
  const sessionId = Math.random().toString(36).substring(7);
  sessions.set(sessionId, { state });
  
  // Build authorization URL
  const authUrl = new URL(OAUTH_CONFIG.authorizationEndpoint);
  authUrl.searchParams.append('client_id', OAUTH_CONFIG.clientId);
  authUrl.searchParams.append('redirect_uri', OAUTH_CONFIG.redirectUri);
  authUrl.searchParams.append('response_type', 'code');
  authUrl.searchParams.append('state', state);
  authUrl.searchParams.append('scope', OAUTH_CONFIG.scope);
  
  // Store session ID in cookie (in production, use proper cookie management)
  res.cookie('session_id', sessionId);
  
  // Redirect to OAuth2 server
  res.redirect(authUrl.toString());
});

// OAuth2 callback
app.get('/callback', async (req, res) => {
  const { code, state, error } = req.query;
  const sessionId = req.headers.cookie?.match(/session_id=([^;]+)/)?.[1];
  
  // Handle errors
  if (error) {
    return res.send(`
      <h1>❌ OAuth2 Error</h1>
      <p>Error: ${error}</p>
      <p>Description: ${req.query.error_description || 'Unknown error'}</p>
      <a href="/">Back to home</a>
    `);
  }
  
  // Validate state (CSRF protection)
  const session = sessions.get(sessionId);
  if (!session || session.state !== state) {
    return res.status(400).send('Invalid state parameter');
  }
  
  try {
    // Exchange authorization code for tokens
    const tokenResponse = await axios.post(OAUTH_CONFIG.tokenEndpoint, new URLSearchParams({
      grant_type: 'authorization_code',
      code,
      redirect_uri: OAUTH_CONFIG.redirectUri,
      client_id: OAUTH_CONFIG.clientId,
      client_secret: OAUTH_CONFIG.clientSecret
    }), {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    });
    
    const { access_token, refresh_token } = tokenResponse.data;
    
    // Get user information
    const userInfoResponse = await axios.get(OAUTH_CONFIG.userInfoEndpoint, {
      headers: { Authorization: `Bearer ${access_token}` }
    });
    
    // Store in session
    session.accessToken = access_token;
    session.refreshToken = refresh_token;
    session.user = userInfoResponse.data;
    sessions.set(sessionId, session);
    
    // Redirect to home page
    res.redirect(`/?session=${sessionId}`);
    
  } catch (error) {
    console.error('OAuth2 error:', error.response?.data || error.message);
    res.status(500).send(`
      <h1>❌ Authentication Failed</h1>
      <p>Error: ${error.message}</p>
      <pre>${JSON.stringify(error.response?.data, null, 2)}</pre>
      <a href="/">Back to home</a>
    `);
  }
});

// Logout
app.get('/logout', (req, res) => {
  const sessionId = req.query.session;
  if (sessionId) {
    sessions.delete(sessionId);
  }
  res.clearCookie('session_id');
  res.redirect('/');
});

// Start the client application
app.listen(PORT, () => {
  console.log('╔════════════════════════════════════════════════════════════╗');
  console.log('║           OAuth2 Example Client Started                   ║');
  console.log('╚════════════════════════════════════════════════════════════╝');
  console.log('');
  console.log(`🚀 Client running at: http://localhost:${PORT}`);
  console.log('');
  console.log('📋 Instructions:');
  console.log('   1. Make sure the OAuth2 server is running on port 3000');
  console.log('   2. Open http://localhost:8080 in your browser');
  console.log('   3. Click "Login with OAuth2"');
  console.log('   4. Login with test credentials (testuser/password)');
  console.log('   5. You will be redirected back with user information');
  console.log('');
});
