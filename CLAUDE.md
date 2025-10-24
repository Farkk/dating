# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Russian-language dating website built with vanilla PHP, HTML/CSS, and MySQL. The site includes three main sections: user search (find), user ratings (rating), and user profiles (user). The architecture is server-rendered with minimal JavaScript, using PHP for dynamic content generation and MySQL for data persistence.

## Database Setup

**Database**: MySQL (default port 3306)
**Database name**: `j27119254_dite`
**Production URL**: https://dating.asmart-test-dev.ru/

### Initialize Database

```bash
# Via phpMyAdmin (recommended on hosting):
# 1. Login to phpMyAdmin
# 2. Select database j27119254_dite
# 3. Go to Import tab
# 4. Upload config/init_db_mysql.sql

# Or via command line:
mysql -u j27119254_dite -p j27119254_dite < config/init_db_mysql.sql
```

### Database Configuration

Database credentials are in `config/db.php`:

- Host: localhost
- Port: 3306
- Database: j27119254_dite
- User: j27119254_dite
- Password: j27119254_dite

### VK App Configuration

VK OAuth credentials are in `config/vk_config.php`:

- App ID: 54267876
- App Secret: MsM9N0LyaS3exARB3GJQ
- Redirect URI: https://dating.asmart-test-dev.ru/auth/vk_callback.php

## Development Server

Since this is a PHP application, you need a web server with PHP and MySQL support:

### Using PHP Built-in Server (for development only)

```bash
php -S localhost:8000
```

### Using Apache/Nginx

- Ensure `.htaccess` settings are respected (UTF-8 encoding, directory index)
- DocumentRoot should point to project root
- PHP with PDO MySQL extension required

## Database Schema

### Core Tables

- **users**: Main user profiles with fields for username, email, password_hash, personal info, bio, rating, and interests (array type)
- **user_photos**: Additional photos for users (references users)
- **meetings**: Tracks scheduled and completed meetings between users with status (pending/confirmed/completed/cancelled)
- **messages**: Direct messages between users
- **likes**: User likes/matches (with uniqueness constraint)
- **meeting_reviews**: Post-meeting ratings and comments (1-5 stars)
- **user_interests**: Alternative interests table for complex search

### Key Relationships

- User ratings are calculated from meeting_reviews
- Likes table supports mutual matching (bidirectional check)
- Meetings link two users (user1_id, user2_id)

## Code Architecture

### Directory Structure

```
/
├── config/          # Database configuration and SQL initialization
│   ├── db.php       # PDO connection setup, executeQuery() helper
│   └── init_db.sql  # Database schema and seed data
├── api/             # Backend API endpoints
│   └── actions.php  # JSON API for likes, meetings, user info
├── find/            # User search/discovery section
│   ├── index.html   # Static version
│   └── index.php    # Dynamic version with database queries
├── rating/          # User rankings page
│   └── index.html
├── user/            # User profile page
│   └── index.html
└── index.html       # Landing page with navigation
```

### PHP Backend Patterns

**Database Helper**: `config/db.php` exports a global `$pdo` instance and `executeQuery($sql, $params)` function that wraps prepared statements.

**API Actions** (`api/actions.php`):

- RESTful-style JSON API using POST with `action` parameter
- Actions: `like_user`, `request_meeting`, `get_user_info`, `get_my_likes`, `get_my_meetings`
- Authentication: Currently hardcoded `$current_user_id = 7` (Anastasia)
- Returns JSON with `{success, message, data}` structure

**Search Implementation** (`find/index.php`):

- Fetches users from database with optional filters (gender, city, age, interests)
- Age calculation done in PHP (calculated from `date_of_birth`)
- MySQL JSON functions used for interests matching (`JSON_CONTAINS`)
- Server-side rendering of user cards

### Frontend Patterns

**Styling**:

- No build process - vanilla CSS in `<style>` tags
- CSS animations via animate.css CDN
- Font Awesome icons for UI elements
- Pink/rose theme (#ff4081 primary color)
- Responsive grid layouts (CSS Grid)

**Structure**:

- Fixed header navigation across all pages
- Card-based layouts for user profiles
- Consistent color scheme and spacing
- Russian language throughout (text, labels, messages)

## Key Implementation Details

### User Authentication

Currently **no real authentication** - user is hardcoded as ID 7 in `api/actions.php`. Any authentication implementation should:

- Use session management (PHP sessions)
- Replace `$current_user_id` variable in API
- Hash passwords with `password_hash()`/`password_verify()`

### Interests Handling

Interests stored as JSON type in `users.interests`:

- Use `JSON_ARRAY('Interest1', 'Interest2')` in SQL or `json_encode()` in PHP
- PHP receives as JSON string, decode with `json_decode($interests, true)`
- Search uses `JSON_CONTAINS(interests, '"Interest")` operator
- Alternative: `user_interests` junction table exists but unused

### Rating System

User ratings calculated from meeting reviews:

- Reviews given after completed meetings (1-5 stars)
- Displayed as decimal (e.g., 4.8/5)
- Calculated via AVG() in SQL or manually updated

### Image Paths

All profile images referenced as relative paths (e.g., `find/images/v1248_985.png`). Images must exist in respective section's `images/` directory.

## Common Tasks

### Add New User

Insert into `users` table with required fields. Password should be hashed:

```sql
INSERT INTO users (username, email, password_hash, first_name, last_name,
                   date_of_birth, gender, city, bio, profile_photo, interests)
VALUES (...);
```

### Query Users by Interest

```sql
SELECT * FROM users
WHERE 'Programming' = ANY(interests);
```

### Check for Mutual Likes

```sql
-- User A likes User B AND User B likes User A
SELECT * FROM likes
WHERE user_id = $user_a AND liked_user_id = $user_b
AND EXISTS (
    SELECT 1 FROM likes
    WHERE user_id = $user_b AND liked_user_id = $user_a
);
```

## Notes

- Character encoding is UTF-8 throughout (enforced in .htaccess)
- No testing framework present
- No dependency management (Composer not used)
- CORS enabled in API (`Access-Control-Allow-Origin: *`)
- Error reporting enabled in `api/actions.php` for debugging
