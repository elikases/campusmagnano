<?php
/**
 * Placas administrables: imagen propia, modo de visualizacion y opciones del slider.
 *
 * Todo lo de este archivo alimenta la pagina /demo (page-demo.php). La portada
 * (home.php) no lo usa y no cambia su comportamiento.
 *
 * @package Campusmagnano
 */

/**
 * Valores por defecto del slider.
 *
 * Centralizados aca para que el meta box y la plantilla no se desincronicen.
 */
function cm_slider_defaults() {
	return array(
		'visibles'  => 3,
		'flechas'   => 1,
		'dots'      => 1,
		'loop'      => 1,
		'autoplay'  => 0,
		'intervalo' => 5,
	);
}

/* -------------------------------------------------------------------------
 * Imagen de la placa
 * ---------------------------------------------------------------------- */

/**
 * Habilita la imagen destacada en las fechas.
 *
 * El CPT `event` se registra en functions.php con supports => title, editor.
 * En vez de tocar ese registro (que es codigo traido de produccion) se suma el
 * soporte aparte, asi el cambio vive junto al resto de la feature.
 */
function cm_placa_soporte_imagen() {
	add_post_type_support( 'event', 'thumbnail' );
}
add_action( 'init', 'cm_placa_soporte_imagen' );

/**
 * Renombra la caja de imagen destacada para que se entienda que es la placa.
 */
function cm_placa_labels( $labels ) {
	$labels->featured_image        = 'Imagen de la placa';
	$labels->set_featured_image    = 'Elegir la imagen de la placa';
	$labels->remove_featured_image = 'Quitar la imagen de la placa';
	$labels->use_featured_image    = 'Usar como imagen de la placa';
	return $labels;
}
add_filter( 'post_type_labels_event', 'cm_placa_labels' );

/**
 * Tamanio recortado para las placas.
 *
 * Retrato 3:4 con recorte duro: la placa es una columna vertical, cualquier
 * otra proporcion deforma o deja franjas. Solo afecta a las imagenes que se
 * suban de aca en adelante.
 */
function cm_placa_image_size() {
	add_image_size( 'placa', 600, 800, true );
}
add_action( 'after_setup_theme', 'cm_placa_image_size' );

/* -------------------------------------------------------------------------
 * Modo de visualizacion de cada placa
 * ---------------------------------------------------------------------- */

/**
 * Devuelve el modo efectivo de una placa: 'texto' o 'imagen'.
 *
 * Si el modo guardado es 'imagen' pero la fecha no tiene imagen destacada,
 * cae a 'texto': una placa vacia y clickeable no es presentable.
 */
function cm_placa_modo( $post_id ) {
	$modo = get_post_meta( $post_id, '_placa_modo', true );
	if ( 'imagen' === $modo && has_post_thumbnail( $post_id ) ) {
		return 'imagen';
	}
	return 'texto';
}

function cm_placa_meta_box() {
	add_meta_box(
		'placa_opciones',
		'Mostrar en la placa',
		'cm_placa_meta_box_html',
		'event',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'cm_placa_meta_box' );

function cm_placa_meta_box_html( $post ) {
	wp_nonce_field( 'cm_placa_guardar', 'cm_placa_nonce' );

	$modo = get_post_meta( $post->ID, '_placa_modo', true );
	if ( 'imagen' !== $modo ) {
		$modo = 'texto';
	}
	?>
	<p style="margin-top:0">
		<label style="display:block; margin-bottom:8px">
			<input type="radio" name="placa_modo" value="texto" <?php checked( 'texto', $modo ); ?> />
			Imagen de fondo + texto (titulo, ciudad, fecha y boton)
		</label>
		<label style="display:block">
			<input type="radio" name="placa_modo" value="imagen" <?php checked( 'imagen', $modo ); ?> />
			Solo la imagen (clickeable, lleva al formulario)
		</label>
	</p>
	<?php if ( ! has_post_thumbnail( $post->ID ) ) : ?>
		<p style="color:#b32d2e; margin-bottom:0">
			Esta fecha todavia no tiene <strong>imagen de la placa</strong>. Sin
			imagen se muestra con el fondo ilustrado del theme, aunque elijas
			"solo la imagen".
		</p>
	<?php endif; ?>
	<p style="color:#646970; margin-bottom:0">
		Tamanio recomendado de la imagen: 720 x 960 px o mas (vertical).
	</p>
	<?php
}

function cm_placa_guardar( $post_id ) {
	if ( ! isset( $_POST['cm_placa_nonce'] ) || ! wp_verify_nonce( $_POST['cm_placa_nonce'], 'cm_placa_guardar' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	$modo = isset( $_POST['placa_modo'] ) ? sanitize_key( $_POST['placa_modo'] ) : 'texto';
	update_post_meta( $post_id, '_placa_modo', 'imagen' === $modo ? 'imagen' : 'texto' );
}
add_action( 'save_post_event', 'cm_placa_guardar' );

/* -------------------------------------------------------------------------
 * Opciones del slider (metas de la pagina del demo)
 * ---------------------------------------------------------------------- */

/**
 * Indica si una pagina usa la plantilla del slider.
 *
 * Sirve tanto en el admin como en el front: is_page_template() depende del
 * query principal y no esta disponible al dibujar los meta boxes.
 */
function cm_es_pagina_slider( $post ) {
	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}
	return 'page-demo.php' === get_page_template_slug( $post->ID ) || 'demo' === $post->post_name;
}

/**
 * Opciones del slider de una pagina, con los defaults ya aplicados.
 */
function cm_slider_opciones( $post_id ) {
	$opciones = cm_slider_defaults();

	foreach ( $opciones as $clave => $default ) {
		$guardado = get_post_meta( $post_id, '_slider_' . $clave, true );
		if ( '' === $guardado ) {
			continue;
		}
		$opciones[ $clave ] = (int) $guardado;
	}

	$opciones['visibles']  = max( 1, min( 5, $opciones['visibles'] ) );
	$opciones['intervalo'] = max( 2, min( 20, $opciones['intervalo'] ) );

	return $opciones;
}

function cm_slider_meta_box() {
	add_meta_box(
		'slider_opciones',
		'Opciones del slider',
		'cm_slider_meta_box_html',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'cm_slider_meta_box' );

function cm_slider_meta_box_html( $post ) {
	if ( ! cm_es_pagina_slider( $post ) ) {
		echo '<p style="color:#646970">Estas opciones se activan cuando la pagina usa la plantilla <strong>Demo Slider</strong> (o su slug es <code>demo</code>). Elegila en "Atributos de pagina" y guarda.</p>';
		return;
	}

	wp_nonce_field( 'cm_slider_guardar', 'cm_slider_nonce' );
	$o = cm_slider_opciones( $post->ID );
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="slider_visibles">Placas por vista</label></th>
			<td>
				<input type="number" id="slider_visibles" name="slider_visibles" min="1" max="5" step="1" value="<?php echo esc_attr( $o['visibles'] ); ?>" />
				<p class="description">Cuantas placas se ven a la vez en pantalla grande. En tablet se muestran 2 y en celular 1, siempre.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Controles</th>
			<td>
				<label><input type="checkbox" name="slider_flechas" value="1" <?php checked( 1, $o['flechas'] ); ?> /> Mostrar flechas de anterior y siguiente</label><br />
				<label><input type="checkbox" name="slider_dots" value="1" <?php checked( 1, $o['dots'] ); ?> /> Mostrar los puntos de abajo</label><br />
				<label><input type="checkbox" name="slider_loop" value="1" <?php checked( 1, $o['loop'] ); ?> /> Volver a la primera placa al pasar la ultima</label>
			</td>
		</tr>
		<tr>
			<th scope="row">Movimiento automatico</th>
			<td>
				<label><input type="checkbox" name="slider_autoplay" value="1" <?php checked( 1, $o['autoplay'] ); ?> /> Pasar las placas solo</label>
				<p>
					<label for="slider_intervalo">Cambiar cada</label>
					<input type="number" id="slider_intervalo" name="slider_intervalo" min="2" max="20" step="1" value="<?php echo esc_attr( $o['intervalo'] ); ?>" />
					segundos
				</p>
				<p class="description">El movimiento automatico se detiene en cuanto el visitante toca el slider, y no se reanuda.</p>
			</td>
		</tr>
	</table>
	<?php
}

function cm_slider_guardar( $post_id ) {
	if ( ! isset( $_POST['cm_slider_nonce'] ) || ! wp_verify_nonce( $_POST['cm_slider_nonce'], 'cm_slider_guardar' ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	$visibles  = isset( $_POST['slider_visibles'] ) ? (int) $_POST['slider_visibles'] : 3;
	$intervalo = isset( $_POST['slider_intervalo'] ) ? (int) $_POST['slider_intervalo'] : 5;

	update_post_meta( $post_id, '_slider_visibles', max( 1, min( 5, $visibles ) ) );
	update_post_meta( $post_id, '_slider_intervalo', max( 2, min( 20, $intervalo ) ) );

	foreach ( array( 'flechas', 'dots', 'loop', 'autoplay' ) as $flag ) {
		update_post_meta( $post_id, '_slider_' . $flag, empty( $_POST[ 'slider_' . $flag ] ) ? 0 : 1 );
	}
}
add_action( 'save_post_page', 'cm_slider_guardar' );
