-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS axow_se;

-- Use the newly created or existing database
USE axow_se;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
    ID CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    valid_token TEXT, -- Stores the current valid single token
    valid_token_type VARCHAR(50), -- Stores the type of the current valid token ('single', 'single-use', 'pair')
    valid_refresh_token TEXT, -- Stores the refresh token if applicable for the token type
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the permissions table
CREATE TABLE IF NOT EXISTS user_permissions (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    string VARCHAR(255) NOT NULL UNIQUE -- e.g., 'articles.add'
);

-- Create the user_permissions table
CREATE TABLE IF NOT EXISTS users_to_permissions (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(36) NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(ID) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES user_permissions(ID) ON DELETE CASCADE,
    UNIQUE (user_id, permission_id) -- Ensure a user can't have the same permission multiple times
);

-- You can optionally add an index on the username for faster lookups
CREATE INDEX idx_username ON users (username);

-- Add the admin user with full permissions
INSERT INTO users (username, password_hash) VALUES ('admin', '$2y$10$jYCWrLmfdm9MGrvKJ5D5yOwS3a0Bi6W5u1w0AXc9.0rIzCkZb9coi'); -- Temp 'admin' password (sha256) CHANGE TO SAFER IN PROD
INSERT INTO user_permissions (string) VALUES ('*');
INSERT INTO users_to_permissions (user_id, permission_id) SELECT u.ID, p.ID FROM users u, user_permissions p WHERE u.username = 'admin' AND p.string = '*';





-- URL Preview Cache
CREATE TABLE url_previewdata_cache (
    url_hash    CHAR(32)  NOT NULL PRIMARY KEY,   -- MD5 hash of the URL
    url         TEXT      NOT NULL,
    previewdata JSON      NOT NULL,               -- the JSON you already echo()
    expires_at  TIMESTAMP NOT NULL,               -- row-specific expiry
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,
    INDEX       (expires_at)
);

-- Make sure the event scheduler is enabled:
SET GLOBAL event_scheduler = ON;

CREATE EVENT purge_expired_cache
    ON SCHEDULE EVERY 1 HOUR
    DO
      DELETE FROM url_previewdata_cache
      WHERE expires_at < NOW();