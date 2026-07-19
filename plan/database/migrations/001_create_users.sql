CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    is_verified TINYINT DEFAULT 0,
    verification_token VARCHAR(100) NULL,
    reset_token VARCHAR(100) NULL,
    reset_token_expires DATETIME NULL,
    avatar VARCHAR(255) DEFAULT 'default.png',
    timezone VARCHAR(100) DEFAULT 'UTC',
    country VARCHAR(100) NULL,
    language VARCHAR(10) DEFAULT 'en',
    occupation VARCHAR(100) NULL,
    birthday DATE NULL,
    bio TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
