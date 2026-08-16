# Deployment Guide

Deploy your Lalaz application to production with confidence.

## Server Requirements

### Minimum Requirements

- **PHP**: 8.1 or higher
- **Web Server**: Nginx or Apache
- **Database**: MySQL 5.7+, PostgreSQL 10+, or SQLite 3
- **PHP Extensions**:
  - PDO (with driver for your database)
  - mbstring
  - openssl
  - JSON
  - cURL

### Recommended

- **PHP**: 8.2+
- **Memory**: 512MB minimum, 1GB+ recommended
- **Storage**: 1GB minimum (varies with application size)
- **OPcache**: Enabled for PHP performance

## Pre-Deployment Checklist

### 1. Environment Configuration

```env
# Production settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Performance
ROUTE_CACHE_ENABLED=true
CONFIG_CACHE_ENABLED=true

# Security
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=strict

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_secure_password
```

### 2. Optimize Dependencies

```bash
# Install production dependencies only
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Generate optimized autoloader
composer dump-autoload --optimize --classmap-authoritative
```

### 3. Run Migrations

```bash
php lalaz migrate
```

### 4. Cache Everything

```bash
# Cache routes (50x faster)
php lalaz route:cache

# Cache config (300x faster)
php lalaz config:cache
```

### 5. Set Permissions

```bash
# Storage and cache directories
chmod -R 775 storage
chmod -R 775 storage/cache
chmod -R 775 storage/logs

# Set owner (adjust for your server)
chown -R www-data:www-data storage
```

## Nginx Configuration

### Basic Configuration

`/etc/nginx/sites-available/lalaz`:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/lalaz/public;

    index index.php index.html;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_comp_level 6;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Enable Site

```bash
sudo ln -s /etc/nginx/sites-available/lalaz /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### SSL with Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Get certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already configured by Certbot)
sudo certbot renew --dry-run
```

Updated configuration with SSL:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/lalaz/public;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;

    # Rest of configuration...
}
```

## Apache Configuration

### Basic Configuration

`.htaccess` in `public/`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Redirect to HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    # Handle front controller
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Deny access to hidden files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Cache static assets
<FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$">
    Header set Cache-Control "max-age=31536000, public, immutable"
</FilesMatch>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

Enable required modules:

```bash
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod deflate
sudo systemctl restart apache2
```

## Database Setup

### MySQL

```sql
CREATE DATABASE lalaz_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lalaz_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON lalaz_app.* TO 'lalaz_user'@'localhost';
FLUSH PRIVILEGES;
```

### PostgreSQL

```sql
CREATE DATABASE lalaz_app;
CREATE USER lalaz_user WITH ENCRYPTED PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE lalaz_app TO lalaz_user;
```

## Queue Workers

### Supervisor Configuration

`/etc/supervisor/conf.d/lalaz-worker.conf`:

```ini
[program:lalaz-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lalaz/lalaz jobs:run
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/lalaz/storage/logs/worker.log
stopwaitsecs=3600
```

Start workers:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start lalaz-worker:*
```

Manage workers:

```bash
# Check status
sudo supervisorctl status lalaz-worker:*

# Restart workers
sudo supervisorctl restart lalaz-worker:*

# Stop workers
sudo supervisorctl stop lalaz-worker:*
```

## Deployment Script

### Automated Deployment

`deploy.sh`:

```bash
#!/bin/bash

set -e

echo "🚀 Starting deployment..."

# Navigate to project
cd /var/www/lalaz

# Maintenance mode (optional)
# echo "⏸️  Entering maintenance mode..."
# touch storage/maintenance.flag

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --classmap-authoritative

# Run migrations
echo "🗄️  Running migrations..."
php lalaz migrate --force

# Clear old caches
echo "🧹 Clearing old caches..."
php lalaz route:cache-clear
php lalaz config:cache-clear

# Generate fresh caches
echo "⚡ Generating fresh caches..."
php lalaz route:cache
php lalaz config:cache

# Set permissions
echo "🔐 Setting permissions..."
chmod -R 775 storage
chown -R www-data:www-data storage

# Restart services
echo "🔄 Restarting services..."
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
sudo supervisorctl restart lalaz-worker:*

# Exit maintenance mode
# echo "✅ Exiting maintenance mode..."
# rm storage/maintenance.flag

echo "✅ Deployment complete!"
```

Make executable:

```bash
chmod +x deploy.sh
```

Run deployment:

```bash
./deploy.sh
```

## Git Deployment

### Setup Git Hook

On server:

```bash
# Initialize bare repository
cd /var/git
git init --bare lalaz.git

# Create post-receive hook
cat > /var/git/lalaz.git/hooks/post-receive << 'EOF'
#!/bin/bash

TARGET="/var/www/lalaz"
GIT_DIR="/var/git/lalaz.git"

echo "Deploying to $TARGET..."

git --work-tree=$TARGET --git-dir=$GIT_DIR checkout -f

cd $TARGET
/var/www/lalaz/deploy.sh

echo "Deployment complete!"
EOF

chmod +x /var/git/lalaz.git/hooks/post-receive
```

On local machine:

```bash
# Add remote
git remote add production ssh://user@yourserver.com/var/git/lalaz.git

# Deploy
git push production main
```

## Docker Deployment

### Dockerfile

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/storage

# Expose port
EXPOSE 9000

CMD ["php-fpm"]
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: lalaz-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
    networks:
      - lalaz-network
    depends_on:
      - db

  nginx:
    image: nginx:alpine
    container_name: lalaz-nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx:/etc/nginx/conf.d
    networks:
      - lalaz-network

  db:
    image: mysql:8.0
    container_name: lalaz-db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: lalaz
      MYSQL_USER: lalaz
      MYSQL_PASSWORD: secret
    volumes:
      - db-data:/var/lib/mysql
    networks:
      - lalaz-network

  worker:
    build: .
    container_name: lalaz-worker
    restart: unless-stopped
    command: php lalaz jobs:run
    volumes:
      - ./:/var/www
    networks:
      - lalaz-network
    depends_on:
      - db

networks:
  lalaz-network:
    driver: bridge

volumes:
  db-data:
```

Deploy with Docker:

```bash
docker-compose up -d
docker-compose exec app php lalaz migrate
docker-compose exec app php lalaz route:cache
docker-compose exec app php lalaz config:cache
```

## Monitoring

### Log Monitoring

```bash
# Real-time logs
tail -f storage/logs/app-$(date +%Y-%m-%d).log

# Error logs
grep ERROR storage/logs/*.log

# Slow requests
grep "Slow request" storage/logs/*.log
```

### Performance Monitoring

Add to cron:

```bash
# /etc/cron.d/lalaz-monitor
*/5 * * * * www-data cd /var/www/lalaz && php lalaz monitor >> /var/log/lalaz-monitor.log 2>&1
```

## Backup Strategy

### Database Backup

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/lalaz"
DATE=$(date +%Y-%m-%d_%H-%M-%S)

# MySQL backup
mysqldump -u lalaz_user -p'password' lalaz_db > $BACKUP_DIR/db-$DATE.sql

# Compress
gzip $BACKUP_DIR/db-$DATE.sql

# Keep only last 7 days
find $BACKUP_DIR -name "db-*.sql.gz" -mtime +7 -delete

echo "Backup completed: $BACKUP_DIR/db-$DATE.sql.gz"
```

Add to cron:

```bash
0 2 * * * /var/www/lalaz/backup.sh
```

### File Backup

```bash
# Backup uploads
tar -czf /var/backups/lalaz/uploads-$(date +%Y-%m-%d).tar.gz public/uploads

# Backup to remote server
rsync -avz /var/www/lalaz user@backup-server:/backups/lalaz/
```

## Troubleshooting

### 500 Internal Server Error

1. Check logs: `tail -f storage/logs/app-*.log`
2. Check PHP logs: `tail -f /var/log/php8.2-fpm.log`
3. Check Nginx logs: `tail -f /var/log/nginx/error.log`
4. Verify permissions: `ls -la storage`

### Routes Not Working

1. Clear route cache: `php lalaz route:cache-clear`
2. Verify `.htaccess` (Apache) or nginx config
3. Check rewrite module: `sudo a2enmod rewrite` (Apache)

### Workers Not Running

1. Check Supervisor status: `sudo supervisorctl status`
2. Check worker logs: `tail -f storage/logs/worker.log`
3. Restart workers: `sudo supervisorctl restart lalaz-worker:*`

## Security Checklist

- [ ] Set `APP_DEBUG=false` in production
- [ ] Use strong database passwords
- [ ] Enable HTTPS with valid certificate
- [ ] Set secure session configuration
- [ ] Enable CSRF protection on forms
- [ ] Apply security headers middleware
- [ ] Restrict file permissions (755/644)
- [ ] Hide sensitive files (.env, composer.json)
- [ ] Enable firewall (ufw, iptables)
- [ ] Keep PHP and dependencies updated
- [ ] Regular security audits

---

**Congratulations!** Your Lalaz application is now deployed to production. 🎉

For support and updates, visit: [Lalaz Framework Documentation](../README.md)
