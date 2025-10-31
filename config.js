// Configuration for the test OAuth2 server
module.exports = {
  // Server configuration
  port: process.env.PORT || 3000,
  host: process.env.HOST || 'localhost',
  
  // OAuth2 configuration
  authorizationCodeLifetime: 600, // 10 minutes
  accessTokenLifetime: 3600, // 1 hour
  refreshTokenLifetime: 86400, // 24 hours
  
  // Pre-configured test clients
  clients: [
    {
      clientId: 'test-client',
      clientSecret: 'test-secret',
      redirectUris: [
        'http://localhost:8080/callback',
        'http://localhost:3001/callback',
        'http://127.0.0.1:8080/callback',
        'http://127.0.0.1:3001/callback'
      ],
      grants: ['authorization_code', 'refresh_token']
    },
    {
      clientId: 'demo-app',
      clientSecret: 'demo-secret',
      redirectUris: [
        'http://localhost:4200/callback',
        'http://localhost:5000/callback'
      ],
      grants: ['authorization_code', 'refresh_token']
    }
  ],
  
  // Pre-configured test users
  users: [
    {
      id: '1',
      username: 'testuser',
      password: 'password',
      email: 'testuser@example.com',
      name: 'Test User',
      firstName: 'Test',
      lastName: 'User'
    },
    {
      id: '2',
      username: 'demo',
      password: 'demo',
      email: 'demo@example.com',
      name: 'Demo User',
      firstName: 'Demo',
      lastName: 'User'
    },
    {
      id: '3',
      username: 'admin',
      password: 'admin',
      email: 'admin@example.com',
      name: 'Admin User',
      firstName: 'Admin',
      lastName: 'User',
      role: 'admin'
    }
  ]
};
