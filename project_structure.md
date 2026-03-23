# Project Structure

The PHP ecommerce project has been refactored to use a centralized configuration system.

## Key Directories & Files

- `/config/`
  - `config.php`: Master include file holding all initialization and constants.
  - `database.php`: Secure array returning DB credentials.
  - `app.php`: Secure array returning SMTP and URL settings.
  - `.htaccess`: Prevents direct web access.
  - `README.txt`: Config documentation.
- `/includes/`
  - `db_connect.php`: Initializes database connection utilizing `config.php`.
  - `mail_config.php`: Includes `config.php` for SMTP constants.
  - `header.php`, `footer.php`, etc.: Frontend components.
- `/admin/`
  - Contains all admin panel logic.
  - `helpers/url.php`: Builds admin URLs utilizing `config.php`.
- `/tracking_module_src/` & `/shipping_module_src/`
  - Isolated modules that hook into the central DB configuration for maintaining dry architecture.
- `index.php`, `checkout.php`, `shop.php`: Frontend entry points.

## Security
The `BASE_PATH` constant is strictly enforced at the top of every config file to prevent direct script access. Furthermore, `.htaccess` is present in `/config` to block any HTTP requests directly to it.
