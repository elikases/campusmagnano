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
| `page-demo.php` | La página `/demo`: las placas en un carrusel (ver más abajo) |
| `single-event.php` | El detalle de cada fecha, con el formulario de inscripción |
| `header.php` / `footer.php` | Cabecera con logo y switch de tema oscuro; pie con redes |
| `functions.php` | Registra los 4 custom post types y sus meta boxes |

El resto (`index.php`, `single.php`, `page.php`, `archive.php`, `search.php`,
`404.php`, `template-parts/`, `inc/`) es Underscores sin modificar.

La portada se sirve por `home.php` porque WordPress la trata como el índice de
entradas. No existe `front-page.php`.

## Página de demo: placas administrables y slider

`http://localhost:8080/demo` — una copia paralela de la sección de fechas de la
portada, con las placas en un carrusel. **La portada no se toca**: sirve para
mostrarle una propuesta al cliente sin tocar lo que está publicado.

Lo que se puede administrar sin tocar código:

| Dónde | Qué |
|---|---|
| Cada fecha → "Imagen de la placa" | Sube la imagen de fondo de esa placa (vertical, 720×960 px o más) |
| Cada fecha → "Mostrar en la placa" | Imagen de fondo + texto, o sólo la imagen (clickeable) |
| Página "Demo Slider" → "Opciones del slider" | Placas por vista, flechas, puntos, vuelta al final, movimiento automático e intervalo |

En los dos modos, el clic en cualquier punto de la placa lleva al mismo lugar
que el botón "Participar": el formulario de esa fecha.

Si una fecha no tiene imagen, la placa se ve como en la portada. Si está en modo
"sólo la imagen" pero no tiene imagen, cae a modo texto.

Archivos de la feature: `page-demo.php`, `inc/placas.php`, `js/carrusel.js` y
`sass/_carrusel.scss`. La spec completa, con las decisiones y su porqué, está en
`docs/spec-slider-placas.md`.

El bloque CSS se llama `.carrusel` y no `.slider` porque `_switch.scss` ya usa
`.slider` para el switch de tema oscuro del header.

## Arreglado acá, pendiente de desplegar

Estos bugs existen en el sitio en vivo y ya están corregidos en este repo. Se
resuelven al desplegar:

- **El ícono flotante de WhatsApp no se ve.** El `css/styles.css` de producción
  apunta a un `images/whatsapp.svg` de 0 bytes. Acá el PNG está en `images/` y
  se escala con `background-size: contain` (el archivo es 128×128 y el botón
  mide 64×64, así que sin eso se veía un cuarto de la imagen ampliado).
- **`functions.php` emitía dos PHP warnings por request.** `my_enqueue_script()`
  usaba `$post` sin declararlo global.
- **Los CPT `coachs` y `modalidades` compartían el mismo `rewrite slug`.** Las
  URLs de uno pisaban las del otro; los coachs quedaban en
  `/modalidades/<slug>/`. Ahora `coachs` usa su propio slug. **Al desplegar, las
  URLs viejas de coachs pasan a 404** — son páginas sin contenido y sin
  plantilla propia, así que el impacto es mínimo, pero si alguna está indexada
  conviene agregar un redirect.

En producción, además, `css/styles.css` y `sass/` están desincronizados en ambas
direcciones: `_grid.scss` quedó vacío en el servidor aunque sus reglas siguen
en el CSS compilado. Acá ya está reconciliado — compilar reproduce el diseño de
producción.

## Deuda abierta

- En producción no hay ningún menú de navegación funcional: los items quedaron
  huérfanos y en borrador.
- `sass/_campus.scss` repite `align-items: start` y `padding: 30px 40px` dos
  veces en el mismo bloque, y `.campus` arrastra un `display: none` que la línea
  siguiente anula. Son restos sin efecto, pero confunden al leer.
- El SCSS usa `@import`, que Dart Sass deprecó en favor de `@use`. Los scripts
  silencian el aviso con `--silence-deprecation=import`. Migrar implica
  renombrar las referencias a variables y mixins en los 19 parciales.
