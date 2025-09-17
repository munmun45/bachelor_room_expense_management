-- Database schema for Bachelor Room Expense Management System

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(100) NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default_avatar.png',
    room_number VARCHAR(20) NULL,
    joined_date DATE NULL,
    occupation VARCHAR(100) NULL,
    emergency_contact VARCHAR(20) NULL,
    kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    id_proof VARCHAR(255) NULL,
    address_proof VARCHAR(255) NULL,
    selfie_verification VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE otp_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NULL,
    otp VARCHAR(6) NOT NULL,
    purpose ENUM('registration', 'password_reset') NOT NULL,
    is_verified BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    INDEX (phone),
    INDEX (email),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE password_reset (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reset_token VARCHAR(255) NULL,
    is_used BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);