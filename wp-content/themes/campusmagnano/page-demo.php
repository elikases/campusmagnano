<?php
/*
Template Name: Demo Slider
*/

/**
 * Demo de las placas administrables en formato slider.
 *
 * Es una copia paralela de la seccion de fechas de home.php: la portada no se
 * toca. Las secciones de acordeon, coachs y modalidades no se replican aca
 * porque el demo es sobre las placas y duplicarlas solo agrega superficie que
 * mantener sincronizada.
 *
 * @package Campusmagnano
 */

get_header();

$opciones = cm_slider_opciones( get_the_ID() );

$fechas = new WP_Query(
	array(
		'post_type'      => 'event',
		'posts_per_page' => -1,
	)
);

$total = $fechas->found_posts;

// Sin placas suficientes para desplazarse, los controles no tienen sentido.
$desplazable = $total > $opciones['visibles'];
$ver_flechas = $desplazable && $opciones['flechas'];
$ver_dots    = $desplazable && $opciones['dots'];

// Los titulos se juntan en la primera pasada para poder etiquetar los dots con
// el nombre de su placa en vez de "ir a la 3".
$titulos = array();
?>
<main>
<a class="whatsapp" target="_blank" rel="noopener" href="https://wa.me/+5493513281636?text=Hola!"></a>

<section>
	<h1><?php bloginfo( 'name' ); ?></h1>

	<?php if ( ! $total ) : ?>

		<p class="carrusel__vacio">Todavia no hay fechas publicadas.</p>

	<?php else : ?>

	<div class="carrusel<?php echo $desplazable ? '' : ' carrusel--pocas'; ?>"
		data-carrusel
		style="--visibles-desktop:<?php echo esc_attr( $opciones['visibles'] ); ?>"
		data-loop="<?php echo esc_attr( $opciones['loop'] ); ?>"
		data-autoplay="<?php echo esc_attr( $desplazable ? $opciones['autoplay'] : 0 ); ?>"
		data-intervalo="<?php echo esc_attr( $opciones['intervalo'] ); ?>"
		role="group"
		aria-roledescription="carrusel"
		aria-label="Fechas del campus">

		<div class="carrusel__viewport" data-carrusel-viewport>
			<ul class="carrusel__track">

				<?php
				while ( $fechas->have_posts() ) :
					$fechas->the_post();

					$id        = get_the_ID();
					$titulo    = get_the_title();
					$titulos[] = $titulo;

					$permalink = get_permalink();
					$modo      = cm_placa_modo( $id );
					$imagen_id = get_post_thumbnail_id( $id );

					$clases = array( 'placa', 'placa--' . $modo );
					if ( $imagen_id ) {
						$clases[] = 'placa--con-imagen';
					}
					?>

					<li class="carrusel__slide">

						<?php if ( 'imagen' === $modo ) : ?>

							<a class="<?php echo esc_attr( implode( ' ', $clases ) ); ?>" href="<?php echo esc_url( $permalink ); ?>">
								<?php
								echo wp_get_attachment_image(
									$imagen_id,
									'placa',
									false,
									array(
										'loading' => 'lazy',
										'alt'     => $titulo,
									)
								);
								?>
							</a>

						<?php else : ?>

							<div class="<?php echo esc_attr( implode( ' ', $clases ) ); ?>"
								<?php if ( $imagen_id ) : ?>
									style="--placa-img:url('<?php echo esc_url( wp_get_attachment_image_url( $imagen_id, 'placa' ) ); ?>')"
								<?php endif; ?>
								>
								<?php
								// Link que cubre la placa entera. Va como hermano del
								// boton y no envolviendolo: un <a> dentro de otro <a>
								// es HTML invalido. aria-hidden + tabindex -1 para no
								// duplicar el enlace en un lector de pantalla: el
								// nombre accesible lo da el boton "Participar".
								?>
								<a class="placa__cover" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true"></a>

								<p class="placa__titulo"><?php echo esc_html( $titulo ); ?></p>

								<?php
								$club     = get_post_meta( $id, 'event_club', true );
								$location = get_post_meta( $id, 'event_location', true );
								$date     = get_post_meta( $id, 'event_date', true );
								?>

								<?php if ( ! empty( $club ) ) : ?>
									<p class="placa__titulo"><?php echo esc_html( $club ); ?></p>
								<?php endif; ?>

								<?php if ( ! empty( $location ) ) : ?>
									<p class="placa__city"><?php echo esc_html( $location ); ?></p>
								<?php endif; ?>

								<?php if ( ! empty( $date ) ) : ?>
									<p class="placa__date"><?php echo esc_html( $date ); ?></p>
								<?php endif; ?>

								<a class="btn placa__btn" href="<?php echo esc_url( $permalink ); ?>">Participar</a>
							</div>

						<?php endif; ?>

					</li>

					<?php
				endwhile;
				wp_reset_postdata();
				?>

			</ul>
		</div>

		<?php if ( $ver_flechas ) : ?>
			<button type="button" class="carrusel__arrow carrusel__arrow--prev" data-carrusel-prev aria-label="Fecha anterior"></button>
			<button type="button" class="carrusel__arrow carrusel__arrow--next" data-carrusel-next aria-label="Fecha siguiente"></button>
		<?php endif; ?>

		<?php if ( $ver_dots ) : ?>
			<div class="carrusel__dots" role="tablist" aria-label="Fechas" data-carrusel-dots>
				<?php foreach ( $titulos as $i => $titulo ) : ?>
					<button type="button"
						role="tab"
						class="carrusel__dot"
						data-carrusel-dot="<?php echo esc_attr( $i ); ?>"
						aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"
						aria-label="Ir a <?php echo esc_attr( $titulo ); ?>"></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>

	<?php endif; ?>
</section>

</main>
<?php
get_footer();
