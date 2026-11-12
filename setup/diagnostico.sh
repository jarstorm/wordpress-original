#!/bin/sh
# Mide el tiempo de respuesta de WordPress activando los plugins de uno en uno.
# Uso: docker compose run --rm wpcli sh /setup/diagnostico.sh
set -u
cd /var/www/html

medir() {
  php -r '
    $ctx = stream_context_create(["http" => ["header" => "Host: localhost:8080\r\n", "timeout" => 60, "follow_location" => 0, "ignore_errors" => true]]);
    $t = microtime(true);
    @file_get_contents("http://wordpress/sobre-nosotros/", false, $ctx);
    printf("%6.2f s", microtime(true) - $t);
  '
}

echo "==> Salida a internet desde el contenedor:"
php -r '$t=microtime(true); $r=@file_get_contents("https://api.wordpress.org/core/version-check/1.7/", false, stream_context_create(["http"=>["timeout"=>20]])); printf("   api.wordpress.org: %s en %.2f s\n", $r ? "OK" : "FALLA", microtime(true)-$t);'
php -r '$t=microtime(true); $r=@file_get_contents("http://localhost:8080/", false, stream_context_create(["http"=>["timeout"=>20]])); printf("   localhost:8080 (loopback): %s en %.2f s\n", $r!==false ? "OK" : "FALLA", microtime(true)-$t);'

ACTIVOS=$(wp plugin list --status=active --field=name)
echo ""
echo "==> Tiempo de /sobre-nosotros/ según plugins activos"
printf "   %-28s %s\n" "(todos activos)" "$(medir)"

wp plugin deactivate --all --quiet
printf "   %-28s %s\n" "(ninguno)" "$(medir)"

for p in $ACTIVOS; do
  wp plugin activate "$p" --quiet
  printf "   %-28s %s\n" "$p" "$(medir)"
  wp plugin deactivate "$p" --quiet
done

echo ""
echo "==> Restaurando plugins..."
# shellcheck disable=SC2086
wp plugin activate $ACTIVOS --quiet
echo "Hecho."
