# campusmagnano

WordPress + Docker para el desarrollo del theme de [campusmagnano.com](https://campusmagnano.com)
("Campus de Basketball").

El repositorio contiene el WordPress completo: el core vive en la raíz y se monta
directamente sobre el docroot del contenedor.

---

## Levantar el entorno

```bash
docker compose up -d
```

| | |
|---|---|
| Sitio | http://localhost:8080 |
| Admin | http://localhost:8080/wp-admin |

La URL del sitio es **dinámica**: sigue el host por el que entrás (`localhost`,
la IP de la LAN o un `*.local`), así que también funciona desde otro dispositivo
de la red sin redirects cruzados ni cookies de dominio equivocado. Está definida
en `WORDPRESS_CONFIG_EXTRA` dentro de `docker-compose.yml`, que `wp-config.php`
evalúa; no hace falta tocar la base de datos.

## Primera instalación

La base de datos no está versionada. En un clon nuevo:

```bash
# 1. Instalar WordPress
docker compose exec -T wpcli wp core install \
  --url="http://localhost:8080" \
  --title="Campus de Basketball" \
  --admin_user="admin" --admin_password="admin" \
  --admin_email="tu@email.com" --skip-email

# 2. Activar el theme y los permalinks
docker compose exec -T wpcli wp theme activate campusmagnano
docker compose exec -T wpcli wp rewrite structure '/%postname%/'

# 3. Cargar el contenido de desarrollo
docker compose exec -T wpcli wp eval-file /var/www/html/dev/seed-content.php

# 4. Refrescar las reglas de rewrite (el CPT `event` usa el slug /fecha/)
docker compose exec -T wpcli wp rewrite flush
```

El paso 4 es necesario: sin él, las URLs `/fecha/<slug>/` devuelven 404 porque
los custom post types se registran después de la instalación.

`dev/seed-content.php` carga las 20 entradas del sitio en producción (5 fechas,
2 características, 4 coachs, 9 modalidades) con sus metadatos. Es idempotente.

## Trabajar en el theme

El theme está en `wp-content/themes/campusmagnano/`.

**Los estilos se editan en `sass/`, no en `css/styles.css`** — ese archivo es
generado. Hay 19 parciales organizados por sección, y los colores y tipografías
salen de `sass/_config.scss`.

```bash
cd wp-content/themes/campusmagnano
npm install          # solo la primera vez
npm run watch        # recompila sass/ -> css/styles.css al guardar
```

El volumen está montado en vivo: guardás, recargás el navegador y ya está. No
hace falta reiniciar contenedores.

Nota: `style.css` en la raíz del theme es el de Underscores sin tocar y
prácticamente no aporta estilos. El diseño real está todo en `css/styles.css`.

## Plantillas

| Archivo | Qué renderiza |
|---|---|
| `home.php` | La portada completa (fechas, acordeón, coachs, modalidades) |
| `single-event.php` | El detalle de cada fecha, con el formulario de inscripción |
| `header.php` / `footer.php` | Cabecera con logo y switch de tema oscuro; pie con redes |
| `functions.php` | Registra los 4 custom post types y sus meta boxes |

El resto (`index.php`, `single.php`, `page.php`, `archive.php`, `search.php`,
`404.php`, `template-parts/`, `inc/`) es Underscores sin modificar.

La portada se sirve por `home.php` porque WordPress la trata como el índice de
entradas. No existe `front-page.php`.

## Deuda conocida

- **El `css/styles.css` de producción está desincronizado de su `sass/`.** En el
  servidor, `_grid.scss` quedó vacío y `_whatsapp.scss` apunta a un PNG que el
  CSS compilado todavía no refleja. Acá ya está reconciliado: compilar reproduce
  el diseño de producción *y* arregla el ícono de WhatsApp, que en el sitio en
  vivo no se ve porque el CSS apunta a un SVG de 0 bytes. **Al desplegar el
  `css/styles.css` de este repo, ese ícono se arregla.**
- `_whatsapp.scss` referencia el PNG con una URL absoluta a `campusmagnano.com`.
  Conviene moverlo a `images/` y usar una ruta relativa como el resto.
- Los CPT `coachs` y `modalidades` comparten el mismo `rewrite slug`
  (`modalidades`) en `functions.php`, lo que hace que las URLs de uno pisen al
  otro.
- En producción no hay ningún menú de navegación funcional: los items quedaron
  huérfanos y en borrador.
