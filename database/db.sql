-- Database schema for Video Portal

-- Drop existing database if it exists
DROP DATABASE IF EXISTS video_portal;

-- Create database
CREATE DATABASE video_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE video_portal;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Create categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Create videos table
CREATE TABLE videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    source ENUM('youtube', 'tiktok', 'facebook') NOT NULL,
    video_id VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255) NULL,
    category_id INT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Create tags table
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Migration for settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` VARCHAR(64) NOT NULL PRIMARY KEY,
  `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create video_tags table (for many-to-many relationship)
CREATE TABLE video_tags (
    video_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (video_id, tag_id),
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@example.com', '$2y$10$HCjFd6ud1vFc3GHg6l.EOudPMTM9g7jGFEXXkVzW.WsnbsKYqwBui', 'Administrator', 'admin');

-- Insert default categories
INSERT INTO categories (name, slug) VALUES 
('Entertainment', 'entertainment'),
('Education', 'education'),
('Sports', 'sports'),
('Music', 'music'),
('Technology', 'technology');

-- Insert default tags
INSERT INTO tags (name, slug) VALUES 
('Trending', 'trending'),
('Popular', 'popular'),
('New', 'new'),
('Featured', 'featured');


-- Add slug column to videos table
ALTER TABLE videos ADD COLUMN IF NOT EXISTS slug VARCHAR(255) AFTER title;

-- Add slug column to categories table
ALTER TABLE categories ADD COLUMN IF NOT EXISTS slug VARCHAR(255) AFTER name;

-- Update category slugs
UPDATE categories SET slug = LOWER(REPLACE(name, ' ', '-')) WHERE slug IS NULL OR slug = '';

-- Update video slugs
UPDATE videos SET slug = LOWER(REPLACE(REPLACE(title, ' ', '-'), '.', '-')) WHERE slug IS NULL OR slug = '';

-- Add indexes for better performance
ALTER TABLE videos ADD INDEX idx_slug (slug);
ALTER TABLE categories ADD INDEX idx_slug (slug);
