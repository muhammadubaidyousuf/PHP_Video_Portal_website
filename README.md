# Video Portal

A modern video sharing platform with an admin panel for managing videos from YouTube, TikTok, and Facebook.

## Features
- Secure admin login
- Dashboard for video management
- Add, edit, and delete videos
- Support for YouTube, TikTok, and Facebook videos
- Category and tag management
- Modern Bootstrap 5 UI
- **Admin Settings page**: Update YouTube API key and slug mode from the panel

## Requirements
- PHP 7.4+
- MySQL 5.7+
- XAMPP (or similar stack)

## Installation
1. Import the database schema from `database/db.sql`
2. (Optional) Run `database/migrate_settings_table.sql` to create the `settings` table for admin config
3. Configure database credentials in `config/database.php`
4. Default admin login: admin@example.com / admin123 (change this after first login)

## Project Structure
- `admin/` - Admin panel files (including settings.php for admin config)
- `assets/` - CSS, JS, images
- `config/` - Configuration files
- `database/` - Database schema & migrations
- `includes/` - PHP includes
- `uploads/` - Thumbnail uploads

## Settings Table
A simple `settings` table is used for storing dynamic admin config (YouTube API key, slug mode, etc). Use the admin panel to update these values securely.
