# Deployment Instructions for codd.cs.gsu.edu

## Step 1: Upload Files

Upload all project files to your codd server:

```bash
# From your local machine
scp -r "/Users/julianrobinson/Desktop/Final Project"/* jrobinson262@codd.cs.gsu.edu:~/public_html/WP/PW/Final/
```

## Step 2: Set Permissions

SSH into codd and run these commands:

```bash
ssh jrobinson262@codd.cs.gsu.edu

# Navigate to project directory
cd ~/public_html/WP/PW/Final

# Set directory permissions
chmod 755 .

# Make setup script executable
chmod +x setup_db.php

# Run the setup script
php setup_db.php
```

The setup script will:
- Create the `reindeer_games.db` file
- Set proper permissions (666)
- Initialize all database tables
- Insert story segments and achievements
- Verify everything is working

## Step 3: Verify Setup

After running setup_db.php, you should see:
```
✓ Database file exists
✓ Database is readable
✓ Database is writable
✓ Successfully connected to database
✓ All tables exist
```

## Step 4: Test the Application

Visit your application in a browser:
```
https://codd.cs.gsu.edu/~jrobinson262/WP/PW/Final/
```

Try creating an account and logging in.

## Troubleshooting

### Permission Denied Error

If you still get "Permission denied":

```bash
# Check directory permissions
ls -ld ~/public_html/WP/PW/Final

# Ensure directory is writable
chmod 755 ~/public_html/WP/PW/Final

# Check if database file exists and has correct permissions
ls -la ~/public_html/WP/PW/Final/reindeer_games.db
chmod 666 ~/public_html/WP/PW/Final/reindeer_games.db
```

### Database Not Creating

If the database file cannot be created:

```bash
# Check disk quota
quota -v

# Create manually
touch ~/public_html/WP/PW/Final/reindeer_games.db
chmod 666 ~/public_html/WP/PW/Final/reindeer_games.db
```

### View PHP Error Logs

```bash
# Check PHP error log (location may vary)
tail -f ~/public_html/WP/PW/Final/error_log
# or
tail -f /var/log/apache2/error.log
```

### SQLite Lock Files

SQLite creates temporary lock files. Ensure the directory is writable:

```bash
chmod 755 ~/public_html/WP/PW/Final
```

## Common Issues

1. **"Database connection failed"** - Run `setup_db.php`
2. **"Permission denied"** - Check file/directory permissions (chmod 666 for .db, 755 for directory)
3. **"Cannot create database file"** - Directory not writable or disk quota exceeded
4. **Login fails silently** - Database exists but has no write permissions

## Quick Fix Commands

```bash
cd ~/public_html/WP/PW/Final
chmod 755 .
touch reindeer_games.db
chmod 666 reindeer_games.db
php setup_db.php
```
