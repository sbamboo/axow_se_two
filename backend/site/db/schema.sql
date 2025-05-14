-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS axow_se;

-- Use the newly created or existing database
USE axow_se;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
    ID CHAR(36) PRIMARY KEY DEFAULT (UUID()),
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    permissions TEXT, -- Store permissions as a delimited string (e.g., 'articles.add; articles.modify')
    valid_token TEXT, -- Stores the current valid single token
    valid_token_type VARCHAR(50), -- Stores the type of the current valid token ('single', 'single-use', 'pair')
    valid_refresh_token TEXT, -- Stores the refresh token if applicable for the token type
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- You can optionally add an index on the username for faster lookups
CREATE INDEX idx_username ON users (username);

-- Add the admin user with full permissions
INSERT INTO users (username, password_hash, permissions) VALUES ('admin', '$2y$10$jYCWrLmfdm9MGrvKJ5D5yOwS3a0Bi6W5u1w0AXc9.0rIzCkZb9coi', '*'); -- Temp 'admin' password (sha256) CHANGE TO SAFER IN PROD
