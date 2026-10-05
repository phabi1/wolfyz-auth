CREATE TABLE IF NOT EXISTS auth_user (
    id VARCHAR(64) NOT NULL PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(190) NOT NULL,
    given_name VARCHAR(190) NULL,
    family_name VARCHAR(190) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_user_token (
    id VARCHAR(64) NOT NULL PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    token_type VARCHAR(80) NOT NULL,
    token CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NULL,
    INDEX user_token_subject (user_id, token_type),
    CONSTRAINT user_token_user FOREIGN KEY (user_id) REFERENCES auth_user (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_oauth_client (
    id VARCHAR(80) NOT NULL PRIMARY KEY,
    secret VARCHAR(255) NULL,
    name VARCHAR(190) NOT NULL,
    redirect_uris TEXT NOT NULL,
    is_confidential TINYINT(1) NOT NULL DEFAULT 0,
    scopes VARCHAR(255) NOT NULL DEFAULT 'openid profile email',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_oauth_scope (
    id VARCHAR(80) NOT NULL PRIMARY KEY,
    description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_oauth_auth_code (
    id VARCHAR(100) NOT NULL PRIMARY KEY,
    client_id VARCHAR(80) NOT NULL,
    user_id VARCHAR(64) NOT NULL,
    scopes VARCHAR(255) NOT NULL,
    redirect_uri VARCHAR(255) NULL,
    nonce VARCHAR(255) NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_oauth_access_token (
    id VARCHAR(100) NOT NULL PRIMARY KEY,
    client_id VARCHAR(80) NOT NULL,
    user_id VARCHAR(64) NULL,
    scopes VARCHAR(255) NOT NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_oauth_refresh_token (
    id VARCHAR(100) NOT NULL PRIMARY KEY,
    access_token_id VARCHAR(100) NOT NULL,
    revoked TINYINT(1) NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO auth_oauth_scope (id, description) VALUES
    ('openid', 'OpenID Connect'),
    ('profile', 'Basic profile information'),
    ('email', 'Email address'),
    ('api', 'Access to the Wolf API');

-- Matches club/public/config.json ("clientId": "club", "redirectUri": "http://localhost:4200")
INSERT IGNORE INTO auth_oauth_client (id, secret, name, redirect_uris, is_confidential, scopes) VALUES
    ('club', NULL, 'Club', 'http://localhost:4200', 0, 'openid profile email api');
