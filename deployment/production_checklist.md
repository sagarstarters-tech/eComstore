# Production Checklist — sagarstarters.com

## Pre-Launch Checklist

### Server & Hosting
- [ ] PHP 8.x enabled on hosting
- [ ] mod_rewrite enabled (Apache) or rewrite rules configured (Nginx)
- [ ] HTTPS / SSL certificate installed and active  
- [ ] `.htaccess` HTTPS redirect uncommented
- [ ] `.htaccess` WWW redirect uncommented
- [ ] `.htaccess` HSTS header uncommented
- [ ] `.htaccess` Content-Security-Policy header configured
- [ ] Directory listing disabled (`Options -Indexes`)

### Environment
- [ ] `.env` → `APP_ENV=production`
- [ ] `.env` → `SITE_URL=https://www.sagarstarters.com`
- [ ] `.env` → `DB_*` credentials updated for production DB
- [ ] `.env` is NOT accessible via browser (403)
- [ ] Debug files blocked (404/403 on browser access)

### Database
- [ ] Production DB created and imported
- [ ] DB credentials are different from local dev
- [ ] Strong DB password set
- [ ] DB user has minimal required privileges (SELECT, INSERT, UPDATE, DELETE only — no DROP/CREATE)
- [ ] DB indexes applied (run admin DB optimizer)

### Security
- [ ] `admin/` is password-protected or IP-restricted at server level
- [ ] Rate limiting active on login pages
- [ ] All form inputs sanitized
- [ ] CSRF tokens active on all forms
- [ ] File upload restricted to image types only
- [ ] Error display disabled (check `php.ini` or `.htaccess`)

### SEO
- [ ] `sitemap.xml` accessible at `https://www.sagarstarters.com/sitemap.xml`
- [ ] `robots.txt` accessible and correct
- [ ] All pages have unique `<title>` and `<meta description>`
- [ ] Canonical URLs set
- [ ] OpenGraph tags present
- [ ] Google Search Console submitted

### Performance
- [ ] GZIP headers active (check via browser DevTools → Network → Headers)
- [ ] Browser cache headers active for images/CSS/JS
- [ ] Images have `loading="lazy"` on non-above-fold images
- [ ] No unused scripts loading in production

### Final Checks
- [ ] All forms tested end-to-end
- [ ] Checkout & payment tested with real cards (live mode)
- [ ] Order notification emails sending correctly
- [ ] Admin panel accessible and functional
- [ ] Mobile responsive — tested on real devices
- [ ] Custom 404 page working
- [ ] Maintenance mode works and can be toggled from admin
- [ ] Sitemap submitted to Google Search Console
