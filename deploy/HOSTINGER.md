# Hostinger production deployment

This project targets PHP 8.3+, MySQL, Apache, and Laravel 13. The compiled Vite files under `public/build` are committed so Node.js is not required on Hostinger.

## 1. Configure the website

1. In hPanel, create the website and enable SSL.
2. Set the website PHP version to PHP 8.3 or newer.
3. Enable these PHP extensions: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Iconv, JSON, LibXML, Mbstring, OpenSSL, PDO MySQL, Session, Tokenizer, XML, and XMLWriter.
4. Preferred setup: point the domain document root to the repository's `public` directory. If hPanel keeps the repository directly in `public_html`, the root `.htaccess` included in this project safely forwards requests into `public/`.

## 2. Create the production database

Create a MySQL database and user in hPanel. Record the database name, username, password, and host exactly as displayed by Hostinger.

Do not import local development users unless they are intended for production. Migrations create the complete schema.

## 3. Configure environment secrets

From SSH, in the project directory:

```bash
cp deploy/hostinger.env.example .env
nano .env
php artisan key:generate
```

Set the real `APP_URL`, MySQL credentials, SMTP credentials, and Microsoft Graph values. Never commit `.env`.

For Microsoft 365 registration OTP delivery, `MS365_TENANT_ID`, `MS365_CLIENT_ID`, `MS365_CLIENT_SECRET`, and `MS365_SENDER_EMAIL` must all be valid. The Azure application must have the required Microsoft Graph application mail permission with administrator consent.

## 4. Deploy

After cloning or pulling the repository through hPanel Git deployment or SSH, run:

```bash
bash deploy/hostinger-deploy.sh
```

If SSH Composer is unavailable, run this locally before uploading:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
```

Upload the resulting project, including `vendor/` and `public/build/`, but never upload the local `.env` over the production `.env`.

## 5. Add the Laravel scheduler cron job

In hPanel, create a custom cron job that runs every minute. Replace the username and domain:

```text
/usr/bin/php /home/u12345678/domains/your-domain.example/public_html/artisan schedule:run
```

If the repository is in a subdirectory, include that directory before `/artisan`. Hostinger's cron form may reject shell redirection characters, so enter only the command above.

The current app uses `QUEUE_CONNECTION=sync` for compatibility with shared hosting and does not require a permanent queue worker.

## 6. Verify production

Run:

```bash
php artisan about
php artisan migrate:status
php artisan schedule:list
php artisan storage:link
```

Then verify:

- `https://your-domain.example/up` returns a successful response.
- Login works for each portal.
- Uploaded profile photos remain visible after refresh.
- Student registration receives a real Microsoft 365 OTP.
- `APP_DEBUG=false` and the page never displays stack traces.
- HTTPS is active and session cookies are secure.

## Updating the application

Before every release, run locally:

```bash
composer test
npm ci
npm run build
```

Commit the generated `public/build` files, push, deploy on Hostinger, and run `bash deploy/hostinger-deploy.sh`.

For rollback, restore both the previous application commit and a compatible database backup. Always create a Hostinger backup before migrations that remove or transform data.
