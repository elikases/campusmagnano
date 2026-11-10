# Spec — Placas con imagen propia y slider (página `/demo`)

Estado: **implementado y en produccion**. La seccion 13 lista lo que se
desvio de la propuesta al escribir el codigo; la 14, lo que cambio despues de
mostrarlo en pantalla y en un telefono real. Donde las dos difieren, manda la 14.
Fecha: 2026-11-07.

---

## 1. Objetivo

Hoy la portada muestra las 5 fechas ("placas") en una fila fija, con el fondo
ilustrado del theme y el texto compuesto desde los metadatos. Se busca:

1. **Imagen propia por placa.** El cliente sube una imagen desde el admin y esa
   imagen reemplaza el fondo ilustrado de esa placa.
2. **Modo de visualización por placa.** Dos opciones:
   - `texto` — la imagen va de fondo y encima se muestra el contenido actual
     (título, club, ciudad, fecha, botón "Participar").
   - `imagen` — se muestra **solo** la imagen, sin texto ni botón. La imagen
     completa es clickeable y lleva al mismo destino que "Participar".
3. **Carrusel.** Las placas pasan a ser un slide con flechas anterior/siguiente
   y dots abajo para moverse entre ellas.
4. **Todo autoadministrable** desde wp-admin, sin tocar código.

Se construye en una **página nueva** (`/demo`) para presentarla al cliente sin
alterar la portada actual.

---

## 2. Alcance

### Se toca

| Archivo | Cambio |
|---|---|
| `page-demo.php` | **nuevo** — plantilla de la página `/demo` |
| `inc/placas.php` | **nuevo** — metas, meta boxes, helpers, tamaño de imagen |
| `js/slider.js` | **nuevo** — controlador del carrusel |
| `sass/_slider.scss` | **nuevo** — estilos de `.placa` y `.slider` |
| `sass/styles.scss` | +1 línea: `@import 'slider.scss';` |
| `functions.php` | +2 líneas: `require` de `inc/placas.php` y encolado condicional del JS |
| `dev/seed-content.php` | crear la página `demo` en el seed |
| `README.md` | documentar la página demo |

### NO se toca

- **`home.php`.** La portada queda exactamente como está. Es el requisito
  explícito: el demo se presenta en paralelo.
- **`sass/_campus.scss`.** Los estilos de `.fecha` no se modifican ni se
  reutilizan por herencia. El demo usa el namespace nuevo `.placa`.
- **`single-event.php`.** El destino de "Participar" sigue siendo el permalink
  de la fecha, sin cambios.

Esto garantiza que si el cliente rechaza el demo, se borra `page-demo.php` y la
página, y no queda nada roto.

---

## 3. Punto de partida

La placa actual se arma en `home.php:25-60`:

```php
$loop = new WP_Query(array('post_type' => 'event', 'posts_per_page' => -1, 'category' => 'current'));
```

- El CPT `event` está registrado en `functions.php:182` con
  `'supports' => array('title', 'editor')` — **sin `thumbnail`**. Habilitarlo es
  parte de esta spec.
- Los metadatos existentes son `event_location`, `event_date`, `event_club`,
  `event_iframe` (meta box "Detalles del Campus", `functions.php:198`).
- El fondo ilustrado **no** viene de la base de datos: está cableado en
  `_campus.scss` por posición (`&:nth-child(odd)` usa `boy.svg`, `&:nth-child(3)`
  usa `girl.svg`). Por eso hoy no hay forma de cambiarlo desde el admin.
- `'category' => 'current'` es inerte: `event` no está registrado con la
  taxonomía `category`, así que WP_Query ignora el argumento. Se omite en la
  plantilla nueva.
- Hay 5 fechas publicadas (IDs 21-25) y 7 en borrador que no se muestran.

---

## 4. Modelo de datos

### 4.1 Imagen de la placa — imagen destacada

Se habilita `thumbnail` en el CPT `event` y la **imagen destacada** es la imagen
de la placa.

```php
// inc/placas.php
add_post_type_support( 'event', 'thumbnail' );
```

Y se renombra la caja en la pantalla de edición para que el cliente entienda qué
es, con `post_type_labels` (`featured_image`, `set_featured_image`, …):
"Imagen de la placa" / "Elegir la imagen de la placa".

**Por qué la imagen destacada y no un meta propio:** la UI de la mediateca, el
recorte, el `alt`, los tamaños responsivos (`srcset`) y el borrado en cascada ya
vienen resueltos por el core. Un meta con `wp.media` propio serían ~80 líneas de
JS para reimplementar peor lo mismo. El campo está libre: ninguna plantilla usa
la imagen destacada de `event` hoy.

### 4.2 Tamaño de imagen

```php
add_image_size( 'placa', 600, 800, true ); // recorte duro, retrato 3:4
```

La placa es una columna vertical, así que el recorte 3:4 es el que no deforma.
Se le indica al cliente subir **720×960 px o más**. El recorte sólo afecta a las
imágenes que se suban de ahí en adelante; para las ya existentes en la mediateca
hace falta regenerar miniaturas (no es necesario: no hay ninguna en uso).

> **Cambió.** El recorte duro decapitaba los flyers. Hoy es
> `add_image_size( 'placa', 1080, 1350, false )`. Ver 14.1.

### 4.3 Modo de visualización

| Meta | Valores | Default |
|---|---|---|
| `_placa_modo` | `texto` \| `imagen` | `texto` |

Guardado con prefijo `_` para que no aparezca en la caja de campos
personalizados, igual que `_modalidad_options`.

El default es `texto` por una razón concreta: si el cliente sube una imagen y no
toca nada más, ve la placa como la conoce con su imagen de fondo. El modo
`imagen` es la decisión explícita de ocultar el texto.

**Fallback.** Si `_placa_modo` es `imagen` pero no hay imagen destacada, se
renderiza en modo `texto`. Una placa vacía y clickeable no es presentable.

### 4.4 Opciones del slider

Viven como metas de la **página** `/demo`, no en una pantalla de opciones nueva:

| Meta | Tipo | Default |
|---|---|---|
| `_slider_visibles` | int 1-5 (placas por vista en desktop) | `3` |
| `_slider_flechas` | bool | `1` |
| `_slider_dots` | bool | `1` |
| `_slider_loop` | bool | `1` |
| `_slider_autoplay` | bool | `0` |
| `_slider_intervalo` | int, segundos 2-20 | `5` |

**Por qué en la página y no en el Customizer:** las opciones viajan con la
página. Cuando el demo se apruebe y haya que llevarlo a la portada, se copia la
plantilla y las metas se migran con un `update_post_meta`; no quedan opciones
globales huérfanas si el demo se descarta. Además el cliente las encuentra donde
está editando, sin aprender otra pantalla.

Autoplay arranca **apagado**: en una presentación al cliente conviene que la
placa se quede quieta, y el movimiento automático es lo primero que molesta en
accesibilidad.

---

## 5. UI de administración

### 5.1 En cada fecha (`event`)

- Caja **"Imagen de la placa"** en la columna lateral (la imagen destacada
  renombrada).
- En la caja existente **"Detalles del Campus"** se agrega al final:

```
Mostrar en la placa:
  ( ) Imagen de fondo + texto (título, ciudad, fecha y botón)
  ( ) Solo la imagen (clickeable, lleva al formulario)
```

Dos radios, no un checkbox: la etiqueta de cada opción dice qué va a pasar. Un
checkbox "ocultar texto" obliga a deducirlo.

Se reutiliza el nonce y el `save_post` que ya tiene el meta box de modalidades
como patrón (`functions.php:302`), pero con un nonce propio
(`placa_nonce`) y verificación de `current_user_can('edit_post')`. El
`save_event_meta_boxes()` actual (`functions.php:229`) **no** verifica nonce ni
capacidades; el código nuevo no replica eso.

### 5.2 En la página `/demo`

Meta box **"Opciones del slider"**, debajo del editor, con los 6 campos de 4.4.
Se muestra sólo si la página usa la plantilla del demo
(`is_page_template` no sirve en el admin: se chequea `$post->post_name === 'demo'`
o el template asignado vía `get_page_template_slug()`).

---

## 6. Markup

```html
<section class="slider" data-slider
         data-visibles="3" data-loop="1" data-autoplay="0" data-intervalo="5">

  <div class="slider__viewport">
    <ul class="slider__track">

      <!-- modo texto -->
      <li class="slider__slide">
        <div class="placa placa--texto" style="--placa-img:url(...)">
          <a class="placa__cover" href="{permalink}" tabindex="-1" aria-hidden="true"></a>
          <p class="placa__titulo">SUMMER CAMP – CÓRDOBA</p>
          <p class="placa__titulo">COLEGIO CASTELFRANCO</p>
          <p class="placa__city">Córdoba</p>
          <p class="placa__date">06, 07, 08 Y 09 de Enero</p>
          <a class="btn placa__btn" href="{permalink}">Participar</a>
        </div>
      </li>

      <!-- modo imagen -->
      <li class="slider__slide">
        <a class="placa placa--imagen" href="{permalink}">
          <img src="..." srcset="..." alt="{title}" width="600" height="800" loading="lazy">
        </a>
      </li>

    </ul>
  </div>

  <button class="slider__arrow slider__arrow--prev" aria-label="Fecha anterior">…</button>
  <button class="slider__arrow slider__arrow--next" aria-label="Fecha siguiente">…</button>

  <div class="slider__dots" role="tablist" aria-label="Fechas">
    <button role="tab" aria-selected="true" aria-label="Ir a SUMMER CAMP – CÓRDOBA"></button>
    …
  </div>
</section>
```

### Cómo se hace clickeable toda la placa

| Modo | Solución |
|---|---|
| `imagen` | La placa **es** el `<a>`. Un solo link, nombre accesible del `alt`. |
| `texto` | `.placa__cover` — un `<a>` hermano en `position:absolute; inset:0`, con `aria-hidden` y `tabindex="-1"`, y el botón "Participar" por encima con `z-index` mayor. |

No se anida el botón dentro de un `<a>` que envuelva la placa: `<a>` dentro de
`<a>` es HTML inválido y los navegadores rompen el DOM al parsearlo. El patrón
del overlay es válido, deja el botón enfocable por teclado y no duplica el link
para un lector de pantalla (de ahí el `aria-hidden`).

Ojo con el `z-index`: `.fecha` ya usa `::before`/`::after` con `z-index:1` para
los degradados laterales. En `.placa` esos pseudo-elementos se reservan para lo
mismo, así que el overlay va en `z-index:2` y el botón en `3`.

### Escapado

`esc_url()` en los href, `esc_attr()` en el `alt` y en los `data-*`,
`esc_html()` en los textos. La imagen se emite con
`wp_get_attachment_image( $id, 'placa', false, array( 'loading' => 'lazy' ) )`,
que ya escapa y agrega `srcset`/`sizes`/`width`/`height`.

---

## 7. Comportamiento del slider

### Técnica: track con `scroll-snap` + controlador JS

```scss
.slider__viewport { overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; }
.slider__slide    { scroll-snap-align: start; flex: 0 0 calc(100% / var(--visibles)); }
```

El desplazamiento real lo hace el navegador. El JS sólo llama a
`viewport.scrollTo()` y escucha `scroll` para saber en qué placa está.

**Por qué así y no una librería** (Swiper, Slick) ni un carrusel con
`transform` propio:

- El swipe táctil, el momentum, el scroll con trackpad y el `scroll-snap` salen
  gratis y nativos. Un carrusel con `transform` necesita handlers de
  `touchstart/move/end` y nunca se siente igual.
- Sin dependencias nuevas: el theme ya carga jQuery 3.7.1 desde un CDN en
  `header.php` y no hace falta sumarle 150 KB de Swiper para 5 placas.
- Sin JS el track sigue siendo scrolleable horizontalmente: degrada a algo
  usable, no a una fila cortada.

El controlador es **vanilla**, ~120 líneas, en un IIFE, y soporta varias
instancias en la misma página (`document.querySelectorAll('[data-slider]')`).

### Requisitos funcionales

| # | Requisito |
|---|---|
| F1 | Flechas prev/next avanzan/retroceden **una** placa. |
| F2 | Sin `loop`, la flecha se deshabilita (`disabled` + `aria-disabled`) en el extremo. Con `loop`, salta al otro extremo. |
| F3 | Los dots son uno por placa; el clic salta a esa placa. |
| F4 | El dot activo refleja la placa visible, incluso si el cambio vino de un swipe. Se deriva del evento `scroll` con `debounce` de ~100 ms. |
| F5 | Flechas izquierda/derecha del teclado mueven el slider cuando el foco está dentro. `Home`/`End` van al primero/último. |
| F6 | Con `autoplay`, avanza cada `intervalo` segundos. **Se detiene** al interactuar (clic, foco, swipe, `pointerenter`) y no se reanuda. |
| F7 | Con `prefers-reduced-motion: reduce`, autoplay no arranca y los saltos son instantáneos (`behavior:'auto'`). |
| F8 | Si hay menos placas que `visibles`, no se renderizan flechas ni dots y el track se centra. |
| F9 | Responsive: `visibles` es el valor configurado en desktop, 2 en tablet (≤768px) y 1 en mobile (≤480px), vía `--visibles` redefinida en media queries. |

### Accesibilidad

- `.slider` con `role="group"` y `aria-roledescription="carrusel"`.
- `aria-live` **no** se usa: el movimiento lo dispara el usuario.
- Los dots como `role="tablist"`/`role="tab"` con `aria-selected`.
- Las placas fuera de vista quedan accesibles (no se les pone `display:none`),
  así que el orden de tabulación sigue el del DOM.

---

## 8. Estilos

`sass/_slider.scss`, importado al final de `styles.scss` (después de
`campus.scss`, para poder pisar si hiciera falta). Variables y mixins ya
disponibles: `$color-primary`, `$color-secondary`, `@include tablet`,
`@include mobile`, `$boy`, `$girl`.

`.placa` **duplica** la tipografía y el botón de `.fecha` en vez de heredarlos.
Suena a repetición, pero el acoplamiento es peor: `.fecha` cablea fondos por
`nth-child` y márgenes negativos pensados para una fila fija de 5, y dentro del
slider esos valores pelean con el `scroll-snap`. Con el namespace separado el
demo se puede ajustar sin riesgo de mover la portada.

- Sin imagen destacada, `.placa` mantiene el look actual: alterna
  `$color-secondary` + `$boy` / `$girl` por `nth-child`, igual que la portada.
- Con imagen, `--placa-img` entra por `style` inline en el elemento
  (`background-image: var(--placa-img)`), con `background-size:cover` y
  `background-position:center`. Es el único inline necesario y es un valor, no
  una regla.
- Modo `imagen`: la `<img>` va `width:100%; height:100%; object-fit:cover;
  display:block`, y la placa fija `aspect-ratio: 3/4` para que todas midan igual
  aunque el cliente suba imágenes de proporciones distintas.
  **Cambió a `contain` y `4/5`** cuando se probó con flyers reales: ver 14.1.
- Modo `texto` con imagen de fondo: se superpone un degradado
  (`::after` o `box-shadow: inset`) para que el texto naranja siga legible sobre
  una foto cualquiera. **Sin esto el texto se pierde** sobre imágenes claras: la
  portada se puede permitir no tenerlo porque los fondos son ilustraciones
  planas que el theme controla.
- Flechas: 44×44 px mínimo (objetivo táctil), posicionadas sobre el viewport en
  desktop y debajo en mobile.

El **tema oscuro** (`#themeSwitch`, `_dark.scss`) tiene que cubrir `.placa`:
fondo, dots y flechas. Es un olvido fácil porque el demo se diseña en claro.

---

## 9. Encolado

```php
// functions.php
if ( is_page_template( 'page-demo.php' ) || is_page( 'demo' ) ) {
    wp_enqueue_script( 'cm-slider', get_template_directory_uri() . '/js/slider.js', array(), _S_VERSION, true );
}
```

Dentro de `campusmagnano_scripts()`, que ya está engachado a
`wp_enqueue_scripts`. El CSS no se encola aparte: el theme sirve un único
`css/styles.css` cableado en `header.php:41`, así que el partial nuevo entra ahí.

Recordatorio del flujo: **se edita `sass/`, no `css/styles.css`**.
`npm run watch` recompila.

---

## 10. La página `/demo`

- Plantilla `page-demo.php`. WordPress la elige automáticamente para la página
  con slug `demo` (jerarquía de plantillas: `page-{slug}.php` antes de
  `page.php`), así que el cliente no tiene que asignar plantilla a mano.
- La página hay que crearla: se agrega al `dev/seed-content.php` para que un
  clon nuevo la tenga.
- La plantilla declara también `/* Template Name: Demo Slider */` para poder
  asignarla a otra página si el cliente quiere otra URL.
- `page-demo.php` repite la estructura de `home.php` (header, `<main>`, WhatsApp,
  `<h1>`) pero con el slider en lugar de `.campus`. Las secciones de acordeón,
  coachs y modalidades **no** se incluyen: el demo es sobre las placas y
  duplicarlas sólo agrega superficie que mantener sincronizada.

---

## 11. Criterios de aceptación

- [ ] `http://localhost:8080/demo` renderiza las 5 fechas publicadas en un slider.
- [ ] Subir una imagen destacada a una fecha la muestra como fondo de esa placa,
      y sólo de esa.
- [ ] Sin imagen destacada, la placa se ve como en la portada.
- [ ] Modo `imagen`: no hay texto ni botón; el clic en cualquier punto de la
      imagen abre el mismo permalink que abría "Participar".
- [ ] Modo `texto`: el clic en la placa y el clic en "Participar" llevan al mismo
      destino; el botón sigue enfocable con `Tab` y activable con `Enter`.
- [ ] `_placa_modo = imagen` sin imagen destacada → cae a modo texto, no a una
      placa vacía.
- [ ] Flechas y dots cambian la placa visible; el dot activo sigue al swipe.
- [ ] Las 6 opciones del slider cambian el comportamiento sin tocar código.
- [ ] Guardar una fecha sin permisos o sin nonce no modifica `_placa_modo`.
- [ ] `home.php` y `_campus.scss` sin cambios: `git diff` vacío para esos dos.
- [ ] La portada se ve idéntica antes y después (comparación del HTML renderizado).
- [ ] 0 errores nuevos en `wp-content/debug.log`.
- [ ] `php -l` limpio en los archivos nuevos.
- [ ] 320px, 480px, 768px y desktop sin scroll horizontal de página.
- [ ] Tema oscuro cubre placas, flechas y dots.

---

## 12. Riesgos y fuera de alcance

- **La portada no cambia.** Si el cliente aprueba el demo, llevarlo a `home.php`
  es un trabajo aparte: hay que decidir qué pasa con la lógica `unafecha`
  (`home.php:36`) y con los márgenes negativos de `.fecha`, que el slider no usa.
- **Desplegar a producción** sigue siendo manual y queda pendiente, igual que los
  arreglos ya cerrados (ver README, "Arreglado acá, pendiente de desplegar").
  El demo no se despliega hasta que el cliente lo apruebe.
- **Las 7 fechas en borrador** no aparecen, igual que hoy. Si el cliente espera
  verlas en el slider, hay que publicarlas.
- **Peso de las imágenes.** Si el cliente sube fotos de 4 MB, el slider carga 5.
  Mitigado con `loading="lazy"` y el tamaño `placa` (que reescala a 1080 px de
  ancho, aunque ya no recorta), pero conviene
  decirle el tamaño recomendado. No se incluye compresión automática.
- **Fuera de alcance:** video de fondo, enlaces distintos del permalink de la
  fecha, reordenar placas con drag & drop (el orden es el de WP_Query: fecha de
  publicación descendente), y traducciones.

---

## 13. Desviaciones durante la implementación

La spec se siguió salvo en cinco puntos, todos por razones que aparecieron al
escribir el código:

1. **El bloque CSS se llama `.carrusel`, no `.slider`.** `sass/_switch.scss` ya
   usa `.slider` para el switch de tema oscuro, y ese switch está en el
   `header.php` de **todas** las páginas. Como `_carrusel.scss` se importa
   después, `position:relative` y `margin-bottom:30px` le habrían pisado el
   switch en todo el sitio, portada incluida. Los metadatos y las funciones PHP
   siguen llamándose `_slider_*` / `cm_slider_*`: ahí no hay colisión y la
   feature es "el slider".

2. **Los radios del modo van en su propia caja lateral** ("Mostrar en la
   placa"), debajo de la imagen de la placa, en vez de al final de "Detalles del
   Campus". Deja los dos campos de la placa juntos y no toca el callback
   existente. La caja avisa en rojo si la fecha todavía no tiene imagen.

3. **Sin `aspect-ratio: 3/4` en la placa.** Una tarjeta 3:4 a un tercio de una
   pantalla ancha mide ~700px de alto. Se usa `min-height` y, en modo imagen, un
   `<img>` en `position:absolute; inset:0; object-fit:cover`: flexbox ya iguala
   la altura de todos los slides, así que las placas quedan parejas sin fijar
   proporción. **Esto se revirtió en parte**: con flyers reales hizo falta
   `aspect-ratio: 4/5` y `object-fit: contain`. Ver 14.1.

4. **`goTo()` distingue avanzar de saltar.** Con `loop` activo, un salto directo
   a una posición fuera de rango daba la vuelta en vez de topar. Ahora sólo los
   avances de a un paso (flechas, teclado, autoplay) dan la vuelta; los dots
   topan. El dot afectado estaba oculto y no era clickeable, así que no era
   visible para el usuario, pero la función confundía dos casos distintos.

5. **Los dots se esconden más allá de la última posición alcanzable.** Con
   `scroll-snap-align:start` la última placa no puede quedar al principio del
   viewport. Con 5 placas y 3 visibles hay 3 posiciones, no 5, así que se
   renderizan 5 dots y el JS esconde los que sobran según el ancho de pantalla.
   Si no, el punto activo quedaba desfasado del scroll real.

### Verificación hecha

- `php -l` limpio en `inc/placas.php`, `page-demo.php`, `functions.php` y
  `dev/seed-content.php`; `node --check` limpio en `js/carrusel.js`.
- `git diff` vacío en `home.php` y `sass/_campus.scss`. El diff de
  `css/styles.css` es **+421 / -0**: ninguna línea existente cambió, y la única
  línea nueva que menciona `.slider` es un comentario.
- La portada sigue devolviendo 200 con sus 5 `.fecha` y sin cargar
  `carrusel.js`.
- Los tres caminos de la placa renderizan bien: modo imagen con `<img>` del
  tamaño `placa` (`loading=lazy`, `alt`, `srcset`), modo texto con `--placa-img`,
  y modo imagen **sin** imagen cayendo a texto.
- En las 5 placas, el enlace de la imagen o del overlay y el del botón
  "Participar" apuntan al mismo `/fecha/<slug>/`.
- Las 6 opciones del admin cambian el HTML servido, y los valores fuera de rango
  se recortan (99 → 5 placas, −5 → 2 segundos).
- La lógica del carrusel se ejercitó con un arnés de DOM en Node: 14
  comprobaciones entre `loop` on y off (topes, vuelta, dots ocultos, punto
  activo, flechas deshabilitadas).
- `dev/seed-content.php` sigue siendo idempotente.
- `wp-content/debug.log` sin errores.

**No verificado en ese momento:** el aspecto visual. La extensión de Chrome no
estaba conectada, así que el render no se pudo mirar. Eso se resolvió después,
mirándolo en pantalla y midiendo en un teléfono Android por `adb` + CDP, y de
ahí salieron los cambios de la sección 14.

---

## 14. Cambios posteriores: de la pantalla al teléfono

Todo lo de abajo salió de mirar el demo renderizado y de medirlo en un Android
real (`adb forward` + Chrome DevTools Protocol). Ninguno estaba en la spec
original porque ninguno se ve leyendo el código.

### 14.1 El recorte decapitaba los flyers

`add_image_size( 'placa', 600, 800, true )` recortaba duro a 3:4. Un flyer de
768×1376 que subió el cliente perdió el **26% de su altura**: el título quedó
cortado al medio y desapareció todo el bloque inferior, "INSCRIPCIONES
ABIERTAS" incluido. Un flyer no es una foto: no tiene margen de sobra para
sacrificar, el contenido llega hasta el borde.

```php
add_image_size( 'placa', 1080, 1350, false );  // sin recorte
```

Con `false` la imagen se reescala para **caber** en la caja sin cortar nada, y
el CSS pasa a `object-fit: contain`. Las imágenes ya subidas se regeneran:

```bash
docker compose exec -T wpcli wp media regenerate --image_size=placa
```

`contain` trae su propio problema: si la placa no tiene la proporción del
flyer, sobran bandas. Se fija `aspect-ratio: 4 / 5`, cerca del vertical típico
de un flyer de redes.

### 14.2 Topar por ancho, no por alto

Con una placa por vista el primer intento fue `max-height: 72vh`. Mal: limitar
el alto de un elemento con `aspect-ratio` lo deja **ancho y bajo**, con el
flyer flotando entre dos bandas grises. Hay que limitar el ancho y dejar que la
proporción calcule el alto:

```scss
max-width: calc(72vh * 4 / 5);
margin: 0 auto;
```

### 14.3 El scroll horizontal en mobile

En un teléfono de 433 px el documento medía **1040 px**: la página entera se
iba al costado, header y footer incluidos. La causa no estaba en el carrusel
sino en `_grid.scss`, que pone `body { display: grid }`. **Un grid item nace
con `min-width: auto`**, o sea que se niega a bajar de su ancho `min-content`;
el carrusel empujaba a `main` a 1040 px y arrastraba a sus hermanos.

```scss
.pagina-demo { min-width: 0; }
```

El primer arreglo colgaba de `.page-template-page-demo main`, y **falló en
producción**: el cliente creó la página sin asignarle la plantilla desde el
desplegable, y `body_class()` sólo escribe `page-template-{slug}` cuando
`_wp_page_template` está seteado a mano. WordPress igual sirve `page-demo.php`
por el slug, así que la página andaba y seguía desbordando. La clase se movió
al `<main>` de la plantilla, que no depende de cómo se creó la página. El mismo
razonamiento vale para el `is_page_template( 'page-demo.php' ) || is_page( 'demo' )`
de `functions.php` y `header.php`: siempre las dos vías.

### 14.4 Versionar el CSS

`header.php` cablea `<link href=".../css/styles.css">` a mano en vez de
encolarlo, y lo servía sin versión. Resultado: se despliega un arreglo y el
visitante sigue viendo el diseño viejo desde la caché. Durante el diagnóstico
en el teléfono costó media hora por esto mismo — las mediciones no reflejaban
el CSS que ya estaba subido.

```php
$cm_css_ver = file_exists( $cm_css ) ? filemtime( $cm_css ) : _S_VERSION;
```

### 14.5 `noindex` en `/demo`

La página es una propuesta para el cliente, no contenido público: indexada
competiría con la portada por las mismas búsquedas. Se filtra `wp_robots` con
`wp_robots_no_robots()` (`noindex, follow`), chequeando plantilla **y** slug
por lo de 14.3.

### 14.6 Fuera el switch de tema oscuro

Se quitó de la portada y del demo (`header.php`, condición `$cm_ocultar_switch`).
Sigue apareciendo en el resto de las plantillas — fechas, páginas sueltas,
archivos, búsqueda, 404 —, lo que deja la navegación inconsistente: el control
aparece y desaparece según la página. Sacarlo de todo el sitio es borrar
`is_front_page() || is_home() ||` de esa condición.

El script inline del footer quedó envuelto en `if (themeSwitch)`: sin eso
`addEventListener` sobre `null` tiraba un TypeError en las páginas donde el
switch ya no se renderiza.

### 14.7 El theme de demo es un theme aparte

Se empaqueta como **Placas Demo** (carpeta `placas-demo`) con
`dev/build-theme-zip.sh`, para instalarlo al lado del de producción sin pisarlo
y poder volver atrás con un clic. Ver el README.

### 14.8 Ver el demo sin el slider

La spec daba por sentado que el demo era el carrusel, así que las únicas
opciones eran sus controles: no había forma de apagar el slider y ver las
placas quietas. Para comparar la propuesta contra la portada hacía falta eso.

Se agregó la meta `_slider_vista` (`slider` | `grilla`, default `slider`) en la
página del demo, con dos radios arriba del resto de las opciones. En `grilla`
la plantilla no emite carrusel: ni viewport, ni `scroll-snap`, ni flechas, ni
puntos, ni autoplay. Las placas van en un `<ul class="placas-grilla">` que es
un grid de tantas columnas como fechas publicadas (topadas en 5), con los
mismos cortes responsive que el carrusel: 2 en tablet y 1 en celular.

**Por qué una meta nueva y no un valor de `_slider_visibles`:** subir "placas
por vista" hasta igualar la cantidad de fechas ya desactivaba los controles por
`$desplazable`, pero sólo en desktop — en tablet y celular el ancho de la placa
está fijo en 2 y 1 por CSS, así que ahí seguía desplazándose, encima sin
flechas ni puntos para guiarlo. El default `slider` deja las páginas que ya
existían viéndose igual.

Tres consecuencias que no son obvias:

- El markup de la placa se mudó a `template-parts/placa.php`. Las dos vistas
  muestran la misma placa y sólo cambia el contenedor; duplicarlo en la
  plantilla obligaba a tocar dos lugares en cada ajuste de diseño.
- Los fondos ilustrados de las placas **sin** imagen propia se aplicaban con
  `.carrusel__slide:nth-child(...)`, que en grilla no matchea. Los tres
  selectores pasaron a `:is(.carrusel__slide, .placas-grilla__item)`: los dos
  argumentos son clases, así que la especificidad no cambia.
- `--columnas-desktop` llega inline y el layout consume `--columnas`, derivada
  de ella, por lo mismo que `--visibles-desktop` en el carrusel: una custom
  property inline no se puede sobreescribir desde una media query.

### 14.9 `/demo2`: la portada con imagen en las placas

Pedido aparte del anterior: una segunda página donde las placas se vean con la
disposición de la portada —las 5 acomodándose 3 y 2— y donde igual se pueda
cargar una imagen por placa, sin que eso cambie cómo se ve en desktop ni en
mobile.

`page-demo2.php` + `sass/_demo2.scss`, con la página `demo2`.

**Reusa `.campus` y `.fecha`,** al revés de lo que decidió la sección 3 para el
carrusel. Ahí el namespace propio se justificaba porque los márgenes negativos
y los fondos por `nth-child` de `.fecha` pelean con `scroll-snap`. Acá no hay
desplazamiento y el requisito es justamente la fidelidad con la portada:
cualquier copia del layout se despegaría del original en el primer ajuste. El
reacomodo 3 + 2 no está cableado en ningún lado — sale de `flex-grow: 1` sin
`flex-basis` y el `flex-wrap` del contenedor, o sea del ancho del contenido. Por
eso se reproduce solo: el markup y el texto son los mismos.

**La imagen va como capa de fondo del `.fecha`, no como `<img>`.** Así la caja
no cambia de tamaño: el alto lo sigue dando el texto, como en la portada. Con
`cover` centrada, una foto horizontal entra sin deformarse. Es lo contrario de
`.placa--imagen` del carrusel, que fija `aspect-ratio: 4/5` y usa
`object-fit: contain` para no decapitar un flyer vertical (14.1): ahí la placa
*es* la imagen, acá la imagen es el fondo de una placa que ya tiene su forma.

**El velo de contraste va en el `background` y no en un `::after`,** porque
`_campus.scss` ya ocupa `::after` y `::before` con las cejas de 20px de los
costados. Tres capas en una sola declaración: velo, imagen y color de base por
si la imagen falla.

**Las cejas se esconden cuando hay imagen.** Son dos franjas claras pegadas a
los bordes dibujadas sobre el fondo: sobre una ilustración plana se leen como un
pliegue, sobre una foto son un tajo blanco. Las placas `nth-child(odd)` de la
portada ya las esconden por lo mismo.

**Especificidad:** los overrides son `.campus .fecha.fecha--con-imagen` (0,3,0),
la misma que `.campus .fecha:nth-child(odd)` de `_campus.scss`, y
`demo2.scss` se importa después en `styles.scss`, así que ganan por orden. Sin
`!important` ni ids, que son más difíciles de desarmar después.

**El modo por fecha (`_placa_modo`) se respeta, pero significa otra cosa.** En
`/demo` "sólo la imagen" reemplaza la placa por la imagen entera, con su propia
proporción. Acá la proporción de la caja es intocable, así que el modo decide
otra cosa: si la imagen ya trae el texto dibujado adentro. En ese caso no se
dibuja el velo y el texto del sitio se esconde con `visibility: hidden`.

**Esas placas dejan la disposición de la portada y pasan a una grilla regular.**
Primero se intentó conservarla escondiendo el texto con `visibility: hidden`,
para que siguiera ocupando su lugar y dando la caja. No alcanza, y el motivo es
de fondo: la disposición de la portada **no es una grilla**. Son 5 cajas de
anchos distintos —487, 530 y 418 px arriba; 712 y 725 abajo, en una pantalla de
1440— que el navegador reparte según el largo de cada título. O sea, cinco
proporciones distintas: 2,15:1, 2,33:1, 3,19:1... Una imagen con el texto
dibujado adentro se recorta distinto en cada una, y no hay diseño que aguante
eso.

Van de a 2 por fila (`flex: 0 0 50%`) y la última se queda con lo que sobre: 3
placas son 2 arriba y 1 abajo, 4 son dos filas parejas, 5 son 2, 2 y 1. El alto
sale de `aspect-ratio`, no del contenido, así que el texto ya no aporta nada y
se saca con `display: none`. Si se quedara ocupando lugar le pondría un alto
mínimo propio a la caja, que volvería a recortar la imagen en cuanto el título
fuera largo.

`flex-grow` va en 0, al revés que en la portada, para que la placa suelta de la
última fila no se estire al ancho completo: estirada mediría el doble que las de
arriba y, como el alto sale de la proporción, también el doble de alto. En su
50% la centra el `justify-content` del `.campus`.

**La proporción es una sola para toda la página** (`1600 / 660`, el formato que
se le pide a las imágenes) y no la de cada imagen: si cada placa usara la suya,
las filas quedarían disparejas en cuanto dos imágenes no midieran exactamente lo
mismo, que es justo lo que pasa cuando las genera una herramienta — pedimos
1600 × 660 y devuelve 1616 × 656. Con la caja y la imagen en la misma
proporción, `cover` no recorta nada.

También se pone `margin-right: 0`: la portada monta cada placa 20px sobre la
siguiente para que las cejas de los costados se lean como un pliegue, y sobre
una imagen eso tapa 20px del diseño.

Efecto colateral: con el texto escondido, el botón "Participar" tampoco está en
el árbol de accesibilidad, y era el que le daba nombre al enlace. En ese modo el
`.fecha__cover` deja de ser `aria-hidden` y pasa a ser el enlace de verdad, con
el título como `aria-label`.

La caja "Opciones del slider" no se registra en esta página: no hay slider, así
que ni siquiera tiene sentido el mensaje que explica cómo activarla. El
`noindex` y el switch de tema oscuro se unificaron en `cm_es_pagina_demo()`,
que cubre los dos demos.

