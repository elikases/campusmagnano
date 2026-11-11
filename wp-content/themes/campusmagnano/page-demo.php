<?php
/*
Template Name: Demo Slider
*/

/**
 * Demo de las placas administrables, en slider o en grilla.
 *
 * Es una copia paralela de la seccion de fechas de home.php: la portada no se
 * toca. Las secciones de acordeon, coachs y modalidades no se replican aca
 * porque el demo es sobre las placas y duplicarlas solo agrega superficie que
 * mantener sincronizada.
 *
 * La vista la elige el cliente desde la pagina (ver cm_slider_vista): en
 * grilla las placas se muestran todas a la vez, sin desplazamiento ni
 * controles, para poder comparar la propuesta contra la portada.
 *
 * @package Campusmagnano
 */

get_header();

$pagina_id = get_the_ID();
$opciones  = cm_slider_opciones( $pagina_id );
$es_grilla = 'grilla' === cm_slider_vista( $pagina_id );

$fechas = new WP_Query(
	array(
		'post_type'      => 'event',
		'posts_per_page' => -1,
	)
);

$total = $fechas->found_posts;

// En grilla entran todas las placas en pantalla: no hay desplazamiento que
// controlar. Y en slider, sin placas suficientes para desplazarse, los
// controles tampoco tienen sentido.
$desplazable = ! $es_grilla && $total > $opciones['visibles'];
$ver_flechas = $desplazable && $opciones['flechas'];
$ver_dots    = $desplazable && $opciones['dots'];

// Columnas de la grilla en desktop: todas las placas en una fila, hasta 5.
// Mas de 5 en una fila las deja demasiado angostas para leerlas.
$columnas = max( 1, min( 5, $total ) );

// Los titulos se juntan en la primera pasada para poder etiquetar los dots con
// el nombre de su placa en vez de "ir a la 3".
$titulos = array();
?>
<main class="pagina-demo">
<a class="whatsapp" target="_blank" rel="noopener" href="https://wa.me/+5493513281636?text=Hola!"></a>

<section>
	<h1 class="titulo-sitio"><?php bloginfo( 'name' ); ?></h1>

	<?php if ( ! $total ) : ?>

		<p class="carrusel__vacio">Todavia no hay fechas publicadas.</p>

	<?php elseif ( $es_grilla ) : ?>

	<ul class="placas-grilla" style="--columnas-desktop:<?php echo esc_attr( $columnas ); ?>">

		<?php
		while ( $fechas->have_posts() ) :
			$fechas->the_post();
			?>
			<li class="placas-grilla__item">
				<?php get_template_part( 'template-parts/placa' ); ?>
			</li>
			<?php
		endwhile;
		wp_reset_postdata();
		?>

	</ul>

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

					$titulos[] = get_the_title();
					?>
					<li class="carrusel__slide">
						<?php get_template_part( 'template-parts/placa' ); ?>
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
