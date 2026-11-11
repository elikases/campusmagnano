<?php
/**
 * Una placa de fecha.
 *
 * Vive aca y no dentro de page-demo.php porque los dos modos de vista (slider
 * y grilla) muestran la misma placa: lo unico que cambia es el contenedor. Si
 * el markup estuviera duplicado en la plantilla, cada ajuste al diseno habria
 * que hacerlo dos veces.
 *
 * Espera estar dentro de un loop de `event`.
 *
 * @package Campusmagnano
 */

$cm_id        = get_the_ID();
$cm_titulo    = get_the_title();
$cm_permalink = get_permalink();
$cm_modo      = cm_placa_modo( $cm_id );
$cm_imagen_id = get_post_thumbnail_id( $cm_id );

$cm_clases = array( 'placa', 'placa--' . $cm_modo );
if ( $cm_imagen_id ) {
	$cm_clases[] = 'placa--con-imagen';
}

if ( 'imagen' === $cm_modo ) :
	?>

	<a class="<?php echo esc_attr( implode( ' ', $cm_clases ) ); ?>" href="<?php echo esc_url( $cm_permalink ); ?>"<?php echo cm_atributos_medicion( $cm_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya vienen escapados. ?>>
		<?php
		echo wp_get_attachment_image(
			$cm_imagen_id,
			'placa',
			false,
			array(
				'loading' => 'lazy',
				'alt'     => $cm_titulo,
			)
		);
		?>
	</a>

	<?php
else :
	?>

	<div class="<?php echo esc_attr( implode( ' ', $cm_clases ) ); ?>"
		<?php if ( $cm_imagen_id ) : ?>
			style="--placa-img:url('<?php echo esc_url( wp_get_attachment_image_url( $cm_imagen_id, 'placa' ) ); ?>')"
		<?php endif; ?>
		>
		<?php
		// Link que cubre la placa entera. Va como hermano del boton y no
		// envolviendolo: un <a> dentro de otro <a> es HTML invalido.
		// aria-hidden + tabindex -1 para no duplicar el enlace en un lector de
		// pantalla: el nombre accesible lo da el boton "Participar".
		?>
		<a class="placa__cover" href="<?php echo esc_url( $cm_permalink ); ?>" tabindex="-1" aria-hidden="true"<?php echo cm_atributos_medicion( $cm_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya vienen escapados. ?>></a>

		<p class="placa__titulo"><?php echo esc_html( $cm_titulo ); ?></p>

		<?php
		$cm_club     = get_post_meta( $cm_id, 'event_club', true );
		$cm_location = get_post_meta( $cm_id, 'event_location', true );
		$cm_date     = get_post_meta( $cm_id, 'event_date', true );
		?>

		<?php if ( ! empty( $cm_club ) ) : ?>
			<p class="placa__titulo"><?php echo esc_html( $cm_club ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $cm_location ) ) : ?>
			<p class="placa__city"><?php echo esc_html( $cm_location ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $cm_date ) ) : ?>
			<p class="placa__date"><?php echo esc_html( $cm_date ); ?></p>
		<?php endif; ?>

		<a class="btn placa__btn" href="<?php echo esc_url( $cm_permalink ); ?>"<?php echo cm_atributos_medicion( $cm_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya vienen escapados. ?>>Participar</a>
	</div>

	<?php
endif;
