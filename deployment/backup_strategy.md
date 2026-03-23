# Backup Strategy — sagarstarters.com

## What to Back Up

| Component | Location | Frequency |
|---|---|---|
| Database | MySQL dump | Daily (automated) or before any deploy |
| Uploaded files | `/assets/images/` | Weekly or before any deploy |
| Configuration | `.env` file | Before every change |
| Full codebase | All PHP files | Before every deploy |

---

## 1. Database Backup

### Manual (phpMyAdmin)
1. Open phpMyAdmin → select `ecommerce_db`
2. Click **Export** → Format: SQL → Custom
3. Tick **Add DROP TABLE** and **IF NOT EXISTS**
4. Click **Go** → save `.sql` file

### Command Line (on VPS/SSH)
```bash
# Replace credentials accordingly
mysqldump -u root -p ecommerce_db > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Automated Daily Cron (VPS)
```bash
# Add this to crontab (crontab -e)
0 2 * * * mysqldump -u root -pYOURPASSWORD ecommerce_db > /home/backups/db_$(date +\%Y\%m\%d).sql
```

---

## 2. File Backup

### Manually (Download via FTP/SFTP)
- Connect with FileZilla or any SFTP client
- Download the entire `/store/` or `/public_html/` folder
- Keep the last 3 versions locally

### Automated (on VPS with cron)
```bash
# Weekly codebase tar
0 3 * * 0 tar -czf /home/backups/store_files_$(date +\%Y\%m\%d).tar.gz /var/www/html/store/
```

---

## 3. Before Deployment (Always do this)

```bash
# 1. Back up DB
mysqldump -u root -p ecommerce_db > pre_deploy_$(date +%Y%m%d).sql

# 2. Back up files
cp -r /var/www/html/store/ /home/backups/pre_deploy_store/

# 3. Deploy new files
# 4. Test site
# 5. If broken — rollback (see below)
```

---

## 4. Rollback Strategy

### DB Rollback
```bash
mysql -u root -p ecommerce_db < pre_deploy_20260304.sql
```

### File Rollback
```bash
# Restore the backup folder
cp -r /home/backups/pre_deploy_store/ /var/www/html/store/
```

### Shared Hosting
- Use **File Manager** in cPanel to restore from backup
- Use phpMyAdmin to import the `.sql` backup

---

## 5. Shared Hosting (cPanel) Backup

1. Login to cPanel → **Backup Wizard**
2. Click **Backup** → **Full Backup** or **Database Backup**
3. Download the backup to your computer
4. Store backups for at least 30 days
