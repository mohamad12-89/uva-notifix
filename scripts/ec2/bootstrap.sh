#!/usr/bin/env bash
set -euo pipefail

sudo apt update
sudo apt install -y nginx unzip git mysql-server php-fpm php-cli php-mbstring php-xml php-curl php-sqlite3 php-mysql php-zip composer certbot python3-certbot-nginx

if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
  # shellcheck disable=SC1090
  source "$HOME/.nvm/nvm.sh"
  nvm install 22
  nvm alias default 22
fi

sudo mkdir -p /var/www/notifix
sudo chown -R "$USER":"$USER" /var/www/notifix

echo "Bootstrap complete. Next run deployment steps in docs/AWS_EC2_DEPLOY.md."
