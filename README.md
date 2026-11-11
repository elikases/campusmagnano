# campusmagnano

WordPress + Docker para el desarrollo del theme de [campusmagnano.com](https://campusmagnano.com)
("Summer camp").

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
  --title="Summer camp" \
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
| `page-demo.php` | La página `/demo`: las placas en un carrusel o en grilla (ver más abajo) |
| `page-demo2.php` | La página `/demo2`: las placas con la disposición exacta de la portada, con imagen propia (ver más abajo) |
| `single-event.php` | El detalle de cada fecha, con el formulario de inscripción |
| `header.php` / `footer.php` | Cabecera con logo; pie con redes. El switch de tema oscuro **no** se muestra en la portada ni en los demos |
| `functions.php` | Registra los 4 custom post types y sus meta boxes |

El resto (`index.php`, `single.php`, `page.php`, `archive.php`, `search.php`,
`404.php`, `inc/`) es Underscores sin modificar. En `template-parts/` lo único
propio es `placa.php`, la placa del carrusel y de la grilla de `/demo`.

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
| Página "Demo Slider" → "Mostrar las placas" | En slider (carrusel) o en grilla: todas las placas a la vez, sin desplazamiento ni controles |
| Página "Demo Slider" → "Opciones del slider" | Placas por vista, flechas, puntos, vuelta al final, movimiento automático e intervalo (sólo en modo slider) |

En los dos modos, el clic en cualquier punto de la placa lleva al mismo lugar
que el botón "Participar": el formulario de esa fecha.

Si una fecha no tiene imagen, la placa se ve como en la portada. Si está en modo
"sólo la imagen" pero no tiene imagen, cae a modo texto.

En modo grilla las placas entran todas en una fila en desktop (hasta 5), 2 en
tablet y 1 en celular, y el `js/carrusel.js` ni se encola: el markup no tiene
carrusel que inicializar.

Archivos de la feature: `page-demo.php`, `template-parts/placa.php`,
`inc/placas.php`, `js/carrusel.js` y `sass/_carrusel.scss`. La spec completa, con las decisiones y su porqué, está en
`docs/spec-slider-placas.md`.

El bloque CSS se llama `.carrusel` y no `.slider` porque `_switch.scss` ya usa
`.slider` para el switch de tema oscuro del header.

## Página de demo 2: las placas como en la portada

`http://localhost:8080/demo2` — la misma sección de fechas, pero con la
**disposición exacta de la portada**: reusa `.campus` y `.fecha` de
`_campus.scss`, así que el reacomodo por `flex-wrap` (en desktop entran 3 y
bajan 2) y los cortes en mobile (2 por fila) son los mismos. Sin carrusel.

Lo único que agrega sobre la portada es la imagen de cada placa, que entra como
capa de fondo del `.fecha`:

| | |
|---|---|
| De dónde sale | "Imagen de la placa" de cada fecha, la misma que usa `/demo` |
| Cómo se recorta | `background-size: cover`, centrada. **La imagen conviene horizontal**: el alto de la caja lo sigue dando el texto, así que una vertical se recorta arriba y abajo |
| Si no hay imagen | La placa queda con la ilustración del theme, igual que en la portada |

Sobre la foto el texto pasa a blanco con sombra (el degradado naranja no se lee
sobre una imagen) y se le suma un velo oscuro de abajo hacia arriba. Las cejas
de 20px de los costados se esconden: sobre una ilustración plana se leen como un
pliegue, sobre una foto son un tajo blanco.

El modo de cada fecha ("Mostrar en la placa") también vale acá, y describe a la
imagen más que a la página:

| Modo | En `/demo2` | Disposición |
|---|---|---|
| Imagen de fondo + texto | La imagen va de fondo, con velo oscuro, y el sitio escribe el título, la ciudad, la fecha y el botón encima | La de la portada: cajas de anchos distintos, que los da el largo de cada título |
| Sólo la imagen | Para imágenes que ya traen el texto dibujado adentro: sin velo y sin texto del sitio | Grilla regular de 2 por fila, con la proporción de la imagen |

**Por qué la grilla regular.** El acomodo de la portada no es una grilla: son 5
cajas de anchos distintos (487, 530, 418 px en una pantalla de 1440) que el
navegador reparte según el largo de cada título. Mientras el texto lo escribe el
sitio eso da igual, pero una imagen que ya trae el texto dibujado necesita una
caja de forma conocida: si no, cada placa la recorta distinto y no hay manera de
diseñar para eso. En este modo la placa toma el ancho de la grilla y el alto de
la proporción, el texto se saca del layout y la imagen entra completa.

Van de a **2 por fila**, y la última se queda con lo que sobre:

| Fechas publicadas | Cómo quedan |
|---|---|
| 3 | 2 arriba y 1 abajo, centrada |
| 4 | dos filas de 2 |
| 5 | 2, 2 y 1 |

La placa suelta de la última fila **no** se estira al ancho completo: se queda en
su mitad y la centra el contenedor. Estirada mediría el doble que las de arriba
y, como el alto sale de la proporción, también el doble de alto.

**El formato de esas imágenes es 1600 × 660 px** (2,42:1), el mismo que usa la
caja. Una imagen que venga un poco corrida (1616 × 656, por ejemplo) pierde unos
pocos píxeles por lado; una vertical se recorta mucho, que es la señal de que
está en el formato equivocado. La proporción es **una sola para toda la página**
a propósito: si cada placa usara la de su imagen, las filas quedarían disparejas
en cuanto dos imágenes no midieran exactamente lo mismo.

En celular va 1 placa por fila: media placa deja la imagen en unos 200 px de
ancho y el texto que trae dibujado no se lee.

En este modo el link que cubre la placa pasa a ser el enlace real (con el título
como nombre accesible), porque el botón "Participar" ya no está a la vista.

Archivos de la feature: `page-demo2.php` y `sass/_demo2.scss`. El clic en
cualquier punto de la placa lleva al formulario de esa fecha, igual que en
`/demo`.

## El título del sitio

El `<h1>` de la portada, `/demo` y `/demo2` es el **Título del sitio** de
Ajustes → Generales (`bloginfo('name')`), no un texto del theme. Cambiarlo ahí
lo cambia en las tres páginas y en la pestaña del navegador.

Los tres llevan `class="titulo-sitio"` y el estilo vive en
`sass/_titulo-sitio.scss`. Va sobre la clase y no sobre el tag `h1` a propósito:
ese mismo `h1` lo usan las fechas, las páginas sueltas, el buscador y el 404, y
ahí un título gigante en mayúsculas no tiene sentido.

| | |
|---|---|
| Tamaño | `clamp(32px, 7vw, 76px)` — fluido, sin media queries y sin saltos entre breakpoints |
| Mayúsculas | Por CSS (`text-transform`), no en el contenido: en Ajustes el título sigue escrito normal, que es como lo lee Google y como aparece en la pestaña |
| La barra naranja | Un `::after`, decorativa, fuera del árbol de accesibilidad |
| Animación | Fade-up de 0,6 s, envuelto en `prefers-reduced-motion: no-preference` |

El color es negro y no el naranja de la marca porque abajo hay cinco placas
llenas de naranja: un título naranja gigante se mezcla con ellas. La barra
alcanza para la marca.

El peso 800 que pide el estilo hoy lo falsea el navegador — ver «Deuda abierta».

## Los demos fuera de los buscadores

Las dos páginas se sirven con `noindex, follow`: es una propuesta, y si Google la
indexa compite con la portada por las mismas búsquedas.

Dos detalles que no son obvios al leer el código:

- **Las comprobaciones de la página van siempre por dos vías**,
  `is_page_template( 'page-demo.php' ) || is_page( 'demo' )`. `body_class()` y
  `is_page_template()` sólo reconocen la plantilla si la página tiene
  `_wp_page_template` asignado a mano desde el desplegable, pero WordPress la
  sirve igual por el slug. Una página creada sin elegir plantilla funciona, y
  cualquier cosa colgada de la clase del `body` se cae en silencio. Por eso el
  arreglo del scroll mobile cuelga de `<main class="pagina-demo">`.
- **`css/styles.css` se versiona con `filemtime()`** en `header.php`. La hoja
  está cableada a mano ahí (no encolada), y sin versión el navegador se queda
  con la copia vieja después de cada despliegue.

## Medición: el pixel de Meta

El pixel `709047704796888` está cableado en `header.php`, dentro del `<head>`, y
**sólo se emite en producción**:

```php
<?php if ( 'production' === wp_get_environment_type() ) : ?>
```

`docker-compose.yml` define `WP_ENVIRONMENT_TYPE = 'development'`, así que desde
local no dispara: si lo hiciera, cada recarga durante el desarrollo ensuciaría
las métricas con visitas que no existen. Producción no define nada y WordPress
cae a `production`.

### Clics por campus

Cada enlace que lleva a la página de una fecha trae `data-campus` y
`data-campus-id`, que pone `cm_atributos_medicion()` (`inc/placas.php`).
`js/medicion.js` escucha el clic y manda a Meta:

```js
fbq('track', 'InitiateCheckout', {
  content_name: 'SUMMER CAMP – CÓRDOBA - Córdoba',
  content_ids: ['25'],
  content_category: 'campus'
})
```

Están instrumentados **todos los caminos de entrada**, no sólo el botón:

| Dónde | Qué enlaces |
|---|---|
| Portada | `.btn.quiero` |
| `/demo2` | `.btn.quiero` y el `.fecha__cover` que cubre la placa |
| `/demo` | `.btn.placa__btn`, el `.placa__cover` y la placa entera cuando es sólo imagen |

El cover no es opcional: en las placas de sólo imagen el botón "Participar" está
en `display: none`, así que justo esas fechas no medirían nada. Un clic manda un
evento y no dos — botón y cover son hermanos, no están anidados, así que
`closest()` encuentra uno solo.

Tres decisiones que no se ven leyendo el código:

- **El nombre lleva la ciudad pegada** (`get_the_title()` + `event_location`)
  porque hay dos fechas que se llaman "SUMMER CAMP" y en el informe de Meta
  quedarían sumadas en una sola línea. `content_ids` lleva el ID de WordPress,
  que no se repite nunca.
- **`InitiateCheckout` y no un evento inventado**: los eventos estándar de Meta
  sirven para optimizar campañas y armar públicos de remarketing; uno con nombre
  propio sólo se puede mirar en el informe.
- **El clic frena la navegación 250 ms.** El navegador cancela las peticiones de
  una página que se está yendo, y el evento se perdería justo en el clic que más
  interesa medir. Con Cmd/Ctrl/Shift no frena nada: esa pestaña no se va a
  ningún lado.

Sin pixel el script no hace nada y los enlaces andan normal. Es el caso de local
y el de cualquier visitante con un bloqueador.

### Lo que no se puede medir

El formulario de cada fecha es un **Google Form embebido en un iframe**
(`event_iframe`). El pixel no puede ver adentro de un iframe de otro dominio, así
que **la inscripción en sí no se mide**: la medición llega hasta la puerta del
formulario. Google Forms tampoco permite redirigir a una URL propia al enviar,
que sería la otra salida.

Para medir inscripciones hay dos caminos, ninguno hecho:

1. Reemplazar el Google Form por un formulario en el sitio (Fluent Forms,
   WPForms, Contact Form 7) y disparar `Lead` al enviar, con el campus adentro.
2. Dejar el Google Form y mandar la conversión desde el servidor, con un Apps
   Script que avise a la API de Conversiones de Meta cuando entra una respuesta
   en la planilla.

## Subir el theme a un servidor

```bash
dev/build-theme-zip.sh
```

Deja en el Escritorio un `placas-demo.zip` instalable desde **Apariencia →
Temas → Añadir nuevo → Subir tema**. Con `DEST=/ruta/salida.zip` se cambia el
destino.

Nota: si el script corre desde esta sesión de Claude Code, el zip **no llega al
Escritorio** — las escrituras fuera del repo quedan en un sandbox y el archivo
nunca toca el disco, aunque el propio comando después lo liste sin error. En ese
caso conviene `DEST=build/placas-demo.zip` y copiarlo a mano.

Por defecto lo arma como un theme aparte, **Placas Demo** (carpeta
`placas-demo`), para poder instalarlo al lado del que está en producción sin
pisarlo: se activa, se muestra, y si algo no gusta se vuelve al anterior con un
clic. El nombre es deliberadamente distinto de «Campusmagnano»: en el listado de
temas no tiene que haber dos tarjetas que empiecen igual, o se activa la
equivocada. La descripción además aclara que es una propuesta.

El theme no cablea su propio nombre en ningún lado — las 13 referencias a rutas
salen de `get_template_directory()` — así que renombrar la carpeta no rompe
nada.

Para generar el que **reemplaza** al de producción:

```bash
dev/build-theme-zip.sh campusmagnano Campusmagnano
```

Ojo: reemplazar borra la carpeta del theme en el servidor. Conviene bajarla
antes por FTP.

El zip excluye `node_modules/` (8 MB de herramientas de compilación que no hacen
nada en el servidor, y muchos hostings limitan la subida a 2 MB).

Después de instalar y activar, en el servidor hace falta:

1. **Ajustes → Enlaces permanentes → Guardar cambios** (el CPT `coachs` cambió
   de slug).
2. Crear una página con slug `demo`. Asignarle la plantilla **Demo Slider** en
   "Atributos de página" es opcional: WordPress sirve `page-demo.php` por el
   slug igual, y la caja "Opciones del slider" también se activa por el slug (al
   crear la página hay que guardarla una vez para que el slug exista).
3. Crear una página con slug `demo2`. La plantilla **Demo 2 (como la
   portada)** también es opcional: WordPress sirve `page-demo2.php` por el slug.
4. Subir los flyers en cada fecha y elegir el modo en "Mostrar en la placa".
5. Si hace falta, cambiar el **Título del sitio** en Ajustes → Generales: es el
   `<h1>` de la portada y de los dos demos.

Los pasos 2 a 5 son obligatorios: las páginas viven en la base de datos, los
flyers en `wp-content/uploads/` y el título en las opciones. Nada de eso viaja
en el theme.

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

- **Montserrat no está cargada.** `_config.scss` declara
  `$font-primary: "Montserrat"` pero el theme no trae ni un `@font-face` ni un
  link a Google Fonts, así que todo el sitio cae a la tipográfica del sistema y
  los pesos (el 800 del título, el `lighter` de los h1/h2) los falsea el
  navegador. Cargarla es el salto visual más grande que queda disponible;
  implica un pedido más a Google Fonts.
- En producción no hay ningún menú de navegación funcional: los items quedaron
  huérfanos y en borrador.
- `sass/_campus.scss` repite `align-items: start` y `padding: 30px 40px` dos
  veces en el mismo bloque, y `.campus` arrastra un `display: none` que la línea
  siguiente anula. Son restos sin efecto, pero confunden al leer.
- El SCSS usa `@import`, que Dart Sass deprecó en favor de `@use`. Los scripts
  silencian el aviso con `--silence-deprecation=import`. Migrar implica
  renombrar las referencias a variables y mixins en los 19 parciales.
