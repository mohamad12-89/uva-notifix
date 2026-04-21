# AWS EC2 Deployment Runbook (Option A)

This guide deploys Notifix as a single Laravel + Vue app on one EC2 instance.

## 1) Required AWS values

- Region: `us-east-1`
- Cognito user pool ID: `us-east-1_y5iIepcok`
- Cognito app client ID: `6mobivpun3a7t3crihqs6vv1e7`
- EC2 host: `35.153.7.86`

## 2) Server bootstrap (Ubuntu)

From your laptop:

```powershell
ssh -i "C:\Users\muham\Downloads\Notifix.pem" ubuntu@35.153.7.86
```

Then run:

```bash
curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
source ~/.nvm/nvm.sh
nvm install 22
nvm use 22
sudo apt update
sudo apt install -y nginx unzip git mysql-server php-fpm php-cli php-mbstring php-xml php-curl php-sqlite3 php-mysql php-zip composer certbot python3-certbot-nginx
```

## 3) App install

```bash
sudo mkdir -p /var/www/notifix
sudo chown -R ubuntu:ubuntu /var/www/notifix
git clone https://github.com/mohamad12-89/uva-notifix.git /var/www/notifix
cd /var/www/notifix
cp .env.example .env
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4) Environment values

Edit `/var/www/notifix/.env` and set:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://35.153.7.86

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/notifix/database/database.sqlite

AWS_DEFAULT_REGION=us-east-1
COGNITO_USER_POOL_ID=us-east-1_y5iIepcok
COGNITO_APP_CLIENT_ID=6mobivpun3a7t3crihqs6vv1e7
COGNITO_ISSUER=https://cognito-idp.us-east-1.amazonaws.com/us-east-1_y5iIepcok
COGNITO_JWKS_URL=https://cognito-idp.us-east-1.amazonaws.com/us-east-1_y5iIepcok/.well-known/jwks.json
VITE_AWS_REGION=us-east-1
VITE_COGNITO_USER_POOL_ID=us-east-1_y5iIepcok
VITE_COGNITO_APP_CLIENT_ID=6mobivpun3a7t3crihqs6vv1e7
```

Create SQLite DB if needed:

```bash
touch /var/www/notifix/database/database.sqlite
php artisan migrate --force
```

## 5) Nginx config

Create `/etc/nginx/sites-available/notifix`:

```nginx
server {
    listen 80;
    server_name 35.153.7.86;

    root /var/www/notifix/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -sf /etc/nginx/sites-available/notifix /etc/nginx/sites-enabled/notifix
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

## 6) HTTPS (when domain is ready)

Once domain DNS points to the EC2 Elastic IP:

```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

## 7) Deploy updates

```bash
cd /var/www/notifix
git pull origin main
composer install --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload nginx
```
