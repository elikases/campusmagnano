#!/usr/bin/env bash
#
# Arma un zip instalable del theme desde el panel de WordPress.
#
# Por defecto lo arma con OTRO nombre de carpeta (campusmagnano-demo), para
# poder instalarlo al lado del theme que ya esta en produccion sin pisarlo:
# se activa, se muestra, y si algo no gusta se vuelve al anterior con un clic
# desde Apariencia -> Temas.
#
# El theme no cablea su propio nombre en ningun lado (todas las rutas salen de
# get_template_directory()), asi que renombrar la carpeta no rompe nada. Lo
# unico que hay que tocar es el "Theme Name" de style.css, que es lo que
# WordPress muestra en el listado.
#
# Uso:
#   dev/build-theme-zip.sh                              # campusmagnano-demo
#   dev/build-theme-zip.sh campusmagnano "Campusmagnano"  # reemplaza al actual
#   DEST=/ruta/salida.zip dev/build-theme-zip.sh
#
set -euo pipefail

SLUG="${1:-campusmagnano-demo}"
NOMBRE="${2:-Campus Magnano (demo)}"
DEST="${DEST:-$HOME/Desktop/${SLUG}.zip}"

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ORIGEN="$RAIZ/wp-content/themes/campusmagnano"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

# node_modules son 8 MB de herramientas de compilacion que no hacen nada en el
# servidor, y muchos hostings tienen el limite de subida en 2 MB.
rsync -a --exclude 'node_modules/' --exclude '.DS_Store' "$ORIGEN/" "$TMP/$SLUG/"

if [ "$SLUG" != "campusmagnano" ]; then
    # Solo la primera aparicion: es la cabecera del theme.
    perl -0pi -e "s/^Theme Name: .*$/Theme Name: $NOMBRE/m unless \$done++" "$TMP/$SLUG/style.css"
fi

rm -f "$DEST"
( cd "$TMP" && zip -rq "$DEST" "$SLUG" )

echo "Listo: $DEST"
echo "  carpeta  : $SLUG"
echo "  nombre   : $(grep -m1 '^Theme Name:' "$TMP/$SLUG/style.css" | sed 's/^Theme Name: //')"
echo "  tamanio  : $(du -h "$DEST" | cut -f1)"
echo "  archivos : $(unzip -l "$DEST" | tail -1 | awk '{print $1}') bytes en $(unzip -l "$DEST" | tail -1 | awk '{print $2}') archivos"
