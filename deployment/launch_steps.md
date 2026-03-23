# Live Launch Steps — sagarstarters.com

Follow these steps in exact order on launch day.

---

## Step 1 — Final Local Test
- [ ] Visit `http://localhost/store/` — homepage loads
- [ ] Add product to cart, complete checkout
- [ ] Confirm order email sends
- [ ] Login to admin panel — all sections working
- [ ] Toggle Maintenance Mode ON → confirm visitor sees maintenance page
- [ ] Toggle Maintenance Mode OFF

---

## Step 2 — Pre-Deploy Backup
```bash
# Backup DB
mysqldump -u root -p ecommerce_db > pre_launch_db.sql

# Backup files (zip for upload)
```
Store the `.sql` file safely.

---

## Step 3 — Configure .env for Production
Edit `.env`:
```env
APP_ENV=production
SITE_URL=https://www.sagarstarters.com
DB_HOST=localhost
DB_NAME=sagarstarters_db
DB_USER=db_user
DB_PASS=strong_password_here
```

---

## Step 4 — Upload Files
1. Connect via FTP/SFTP to your host
2. Upload all files to `/public_html/`
3. Upload and import the SQL file via phpMyAdmin

---

## Step 5 — Enable SSL
1. Install Let's Encrypt SSL via cPanel or Certbot
2. In `.htaccess`, uncomment:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Step 6 — Enable WWW Redirect
In `.htaccess`, uncomment:
```apache
RewriteCond %{HTTP_HOST} !^www\. [NC]
RewriteRule ^ https://www.%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Step 7 — Enable HSTS (After SSL Verified)
In `.htaccess`, uncomment:
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
```

---

## Step 8 — Verify Site Live
- [ ] Visit `https://www.sagarstarters.com` — loads with HTTPS padlock
- [ ] Test `http://sagarstarters.com` → redirects to `https://www.`
- [ ] Visit `/sitemap.xml` — XML loads correctly
- [ ] Visit `/robots.txt` — loads correctly
- [ ] Visit an invalid page — custom 404 shows
- [ ] Visit admin panel — login and all features working
- [ ] Complete a test order (use COD / sandbox PhonePe)

---

## Step 9 — Submit to Google
1. Go to [Google Search Console](https://search.google.com/search-console)
2. Add property → Domain: `sagarstarters.com`
3. Verify via DNS TXT record
4. Submit sitemap: `https://www.sagarstarters.com/sitemap.xml`

---

## Step 10 — Enable Production Monitoring
- Set up uptime monitoring (UptimeRobot / Better Uptime — free tier)
- Enable email alerts for downtime

---

## You're Live! 🚀
