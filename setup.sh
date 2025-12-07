#!/bin/bash

echo "=== Reindeer Games Setup ==="
echo ""

# Try to create database and import schema
echo "Setting up database..."
mysql -u root -p123 -e "CREATE DATABASE IF NOT EXISTS reindeer_games;" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✓ Database created"
    mysql -u root -p123 reindeer_games < schema.sql 2>/dev/null
    if [ $? -eq 0 ]; then
        echo "✓ Schema imported"
        echo ""
        echo "Setup complete! Starting server..."
        echo "Visit: http://localhost:8000"
        echo ""
        php -S localhost:8000
    else
        echo "✗ Failed to import schema"
        echo "Trying to set MySQL password to 123..."
        echo ""
        echo "Please run this command manually:"
        echo "  mysql -u root -p"
        echo ""
        echo "Then inside MySQL, run:"
        echo "  ALTER USER 'root'@'localhost' IDENTIFIED BY '123';"
        echo "  CREATE DATABASE reindeer_games;"
        echo "  USE reindeer_games;"
        echo "  SOURCE schema.sql;"
        echo "  EXIT;"
        echo ""
        echo "Then run: php -S localhost:8000"
    fi
else
    echo "✗ Cannot connect to MySQL"
    echo ""
    echo "Please set your MySQL root password to '123' manually:"
    echo ""
    echo "1. Run: mysql -u root -p"
    echo "2. Enter your current MySQL password"
    echo "3. Then run these commands:"
    echo "   ALTER USER 'root'@'localhost' IDENTIFIED BY '123';"
    echo "   CREATE DATABASE reindeer_games;"
    echo "   USE reindeer_games;"
    echo "   SOURCE schema.sql;"
    echo "   EXIT;"
    echo ""
    echo "4. Then run: ./setup.sh"
fi
