# /config — Deployment Guide for Hostinger

## ⚠️ Security Notice
This folder is **blocked from web access** via `.htaccess`.
Never expose these files publicly.

---

## 🚀 How to Deploy to Hostinger

### Step 1 — Upload Files
Upload the entire `/store` project to Hostinger via FTP or File Manager.
Place files in `public_html/` (or your subdomain root).

### Step 2 — Edit the .env File (Only File You Need to Change!)
Open `/store/.env` and update with your Hostinger database credentials:

```
APP_ENV=production
DB_HOST=localhost
DB_NAME=u123456789_your_db_name
DB_USER=u123456789_your_db_user
DB_PASS=YourSecurePassword123
DB_PORT=3306
```

> **Where to find Hostinger DB credentials:**
> Hostinger Panel → Hosting → Manage → Databases → MySQL Databases

### Step 3 — Update URLs in .env (if needed)
If your store runs at `yourdomain.com/store/`, the `/config/app.php` `store_base_url`
should be `/store/`. If installed at domain root, change to `/`.

### Step 4 — Set Production Mode
In `.env`, ensure:
```
APP_ENV=production
```
This automatically disables `display_errors` and hides sensitive PHP error output.

### Step 5 — Verify PHP Version
Hostinger requires **PHP 8.0+** for this project (uses `?:` typed properties in `Database.php`).
Set PHP version: Hostinger Panel → Hosting → PHP Configuration → PHP 8.1 or 8.2

### Step 6 — Import Database
Import your SQL file via Hostinger's phpMyAdmin (Databases → phpMyAdmin).

---

## 📁 Config File Reference

| File | Purpose | Edit? |
|------|---------|-------|
| `/.env` | **All credentials** | ✅ Yes — this is where you change DB/SMTP settings |
| `/.env.example` | Template (safe to share) | ❌ No |
| `/config/database.php` | Loads .env, defines DB_* constants | ❌ No |
| `/config/app.php` | URLs + SMTP settings | Only for URL changes |
| `/config/config.php` | Bootstrap — loads all configs | ❌ No |
| `/config/Database.php` | Singleton PDO class | ❌ No |
| `/includes/db_connect.php` | Global MySQLi `$conn` | ❌ No |

---

## 🔧 Troubleshooting

### "Connection failed" on Hostinger
- Check `DB_HOST` — Hostinger usually uses `localhost`
- Double-check `DB_NAME`, `DB_USER`, `DB_PASS` from Hostinger panel
- Ensure the DB user has ALL PRIVILEGES on the database

### Blank page / 500 Error  
- Temporarily set `APP_ENV=development` in `.env` to see PHP errors
- Check Hostinger error logs: Panel → Advanced → Error Logs

### .htaccess not working
- Hostinger supports Apache — `.htaccess` should work out of the box
- Ensure `AllowOverride All` is enabled (it is by default on Hostinger shared hosting)
