#!/bin/sh
# Instala y configura la web de Nortia Reformas (empresa ficticia).
# Se ejecuta dentro del contenedor wpcli:
#   docker compose run --rm wpcli sh /setup/setup.sh
set -eu

WP_URL="${WP_URL:-http://localhost:8080}"
SITE_TITLE="Nortia Reformas"
ADMIN_USER="admin"
ADMIN_PASS="admin"
ADMIN_EMAIL="admin@nortiareformas.example"

# Plugins típicos de una pyme española con WordPress
PLUGINS="
advanced-custom-fields
wordpress-seo
contact-form-7
flamingo
cookie-notice
redirection
updraftplus
wordfence
wp-mail-smtp
wp-smushit
duplicate-post
"

cd /var/www/html

echo "==> Esperando a que WordPress y la base de datos estén listos..."
i=0
until [ -f wp-config.php ] && php -r '$m=@new mysqli(getenv("WORDPRESS_DB_HOST"),getenv("WORDPRESS_DB_USER"),getenv("WORDPRESS_DB_PASSWORD"),getenv("WORDPRESS_DB_NAME")); exit($m->connect_errno?1:0);'; do
  i=$((i+1))
  if [ "$i" -gt 60 ]; then echo "ERROR: WordPress no responde. ¿Has hecho 'docker compose up -d'?"; exit 1; fi
  sleep 2
done

if wp core is-installed 2>/dev/null; then
  echo "==> WordPress ya estaba instalado."
else
  echo "==> Instalando WordPress..."
  wp core install --url="$WP_URL" --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" --skip-email --locale=es_ES
fi

echo "==> Idioma y ajustes generales..."
wp language core install es_ES --activate || true
wp option update blogdescription "Reformas integrales, cocinas y baños en Asturias"
wp option update timezone_string "Europe/Madrid"
wp option update date_format "j \d\e F \d\e Y"
wp option update time_format "H:i"
wp option update start_of_week 1
wp option update posts_per_page 6
wp option update default_comment_status open
wp rewrite structure '/%postname%/' --hard

echo "==> Tema Astra..."
wp theme install astra --activate
for t in $(wp theme list --status=inactive --field=name); do wp theme delete "$t" || true; done

echo "==> Plugins..."
wp plugin delete akismet hello 2>/dev/null || true
# shellcheck disable=SC2086
wp plugin install $PLUGINS --activate

echo "==> Traducciones de plugins y tema..."
wp language plugin install --all es_ES || true
wp language theme install --all es_ES || true

echo "==> Contenido de la empresa..."
wp eval-file /setup/content.php

wp rewrite flush --hard
wp cache flush || true

echo ""
echo "======================================================"
echo " Listo: $WP_URL"
echo " Admin: $WP_URL/wp-admin  ($ADMIN_USER / $ADMIN_PASS)"
echo " Editora: marta / marta"
echo "======================================================"
