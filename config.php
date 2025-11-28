<?php
/**
 * OAuth2 Server Configuration
 * 
 * Customize this file to add your own clients, users, and settings
 */

$config = [
    // OAuth2 Configuration
    'authorizationCodeLifetime' => 600,      // 10 minutes
    'accessTokenLifetime' => 3600,           // 1 hour
    'refreshTokenLifetime' => 86400,         // 24 hours
    
    // Session configuration for storing tokens
    'sessionLifetime' => 3600,               // 1 hour
    
    // Pre-configured test clients
    'clients' => [
        [
            'clientId' => 'test-client',
            'clientSecret' => 'test-secret',
            'redirectUris' => [
                'http://localhost:8080/callback',
                'http://localhost:3001/callback',
                'http://127.0.0.1:8080/callback',
                'http://127.0.0.1:3001/callback',
                'http://localhost/callback',
                'http://test-app.local/callback'
            ],
            'grants' => ['authorization_code', 'refresh_token']
        ],
        [
            'clientId' => 'demo-app',
            'clientSecret' => 'demo-secret',
            'redirectUris' => [
                'http://localhost:4200/callback',
                'http://localhost:5000/callback',
                'http://demo-app.local/callback'
            ],
            'grants' => ['authorization_code', 'refresh_token']
        ],
        [
            'clientId' => 'laragon-app',
            'clientSecret' => 'laragon-secret',
            'redirectUris' => [
                'http://localhost/oauth-callback',
                'http://myapp.local/callback',
                'http://myapp.test/callback'
            ],
            'grants' => ['authorization_code', 'refresh_token']
        ],
        [
            'clientId' => 'classlinkid',
            'clientSecret' => 'classlink-secret',
            'redirectUris' => [
                'https://classlink.test/login',
                'http://localhost:3000/login',
                'http://127.0.0.1:3000/login'
            ],
            'grants' => ['authorization_code', 'refresh_token']
        ]
    ],
    
    // Pre-configured test users
    'users' => [
        [
            'id' => '1',
            'username' => 'testuser',
            'password' => 'password',
            'email' => 'testuser@example.com',
            'name' => 'Test User',
            'firstName' => 'Test',
            'lastName' => 'User'
        ],
        [
            'id' => '2',
            'username' => 'demo',
            'password' => 'demo',
            'email' => 'demo@example.com',
            'name' => 'Demo User',
            'firstName' => 'Demo',
            'lastName' => 'User'
        ],
        [
            'id' => '3',
            'username' => 'admin',
            'password' => 'admin',
            'email' => 'admin@example.com',
            'name' => 'Admin User',
            'firstName' => 'Admin',
            'lastName' => 'User',
            'role' => 'admin'
        ],
        [
            'id' => '4',
            'username' => 'laragon',
            'password' => 'laragon',
            'email' => 'laragon@example.com',
            'name' => 'Laragon User',
            'firstName' => 'Laragon',
            'lastName' => 'User'
        ]
    ]
];
