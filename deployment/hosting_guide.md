# Hosting Guide — sagarstarters.com

## Option A: Shared Hosting (Hostinger, GoDaddy, etc.)

### 1. Upload Files
1. Login to cPanel → **File Manager** → open `/public_html/`
2. Upload all project files (zip & extract or use FTP)
3. Ensure the folder structure is at the root:
   ```
   /public_html/
   ├── index.php
   ├── admin/
   ├── assets/
   ├── includes/
   └── ...
   ```

### 2. Configure .env
Edit `.env`:
```env
APP_ENV=production
SITE_URL=https://www.sagarstarters.com
DB_HOST=localhost
DB_NAME=your_db_name
DB_USER=your_db_user
DB_PASS=your_secure_password
DB_PORT=3306
```

### 3. Create Database
1. cPanel → **MySQL Databases**
2. Create db: `sagarstarters_db`
3. Create user with strong password
4. Assign user to db — ALL PRIVILEGES
5. Import SQL via phpMyAdmin

### 4. Set PHP Version
1. cPanel → **MultiPHP Manager** or **PHP Selector**
2. Select PHP 8.1 or 8.2

### 5. Enable SSL
1. cPanel → **SSL/TLS** → **Let's Encrypt** → Install for domain
2. Uncomment HTTPS redirect lines in `.htaccess`

### 6. Point Domain
1. In domain registrar (GoDaddy, Namecheap, etc.)
2. Set **A Record**: `@` → your server IP
3. Set **CNAME**: `www` → `@`
4. Wait 15-60 minutes for propagation

---

## Option B: VPS / Cloud (DigitalOcean, AWS, etc.)

### 1. Server Setup
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install LAMP
sudo apt install apache2 mysql-server php8.2 php8.2-mysql php8.2-curl php8.2-mbstring php8.2-zip libapache2-mod-php -y

# Enable Apache modules
sudo a2enmod rewrite headers deflate expires
sudo service apache2 restart
```

### 2. Upload Code
```bash
# Via SFTP / SCP
scp -r /local/store/ user@your_server_ip:/var/www/html/

# Or clone from Git
cd /var/www/html/
git clone https://github.com/your-repo/store.git
```

### 3. Apache Virtual Host
```apache
<VirtualHost *:80>
    ServerName sagarstarters.com
    ServerAlias www.sagarstarters.com
    DocumentRoot /var/www/html/store
    <Directory /var/www/html/store>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### 4. SSL with Certbot
```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d sagarstarters.com -d www.sagarstarters.com
```

### 5. File Permissions
```bash
sudo chown -R www-data:www-data /var/www/html/store/
sudo find /var/www/html/store/ -type f -exec chmod 644 {} \;
sudo find /var/www/html/store/ -type d -exec chmod 755 {} \;
sudo chmod 600 /var/www/html/store/.env
```

---

## Domain Pointing Guide

| Record Type | Name | Value |
|---|---|---|
| A | `@` | `YOUR_SERVER_IP` |
| A | `www` | `YOUR_SERVER_IP` |
| CNAME | `www` | `sagarstarters.com` |

> Changes propagate within **15 minutes to 48 hours**. Use [whatsmydns.net](https://www.whatsmydns.net) to check.
