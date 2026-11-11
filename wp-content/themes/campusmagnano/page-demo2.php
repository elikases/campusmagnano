<?php
/*
Template Name: Demo 2 (como la portada)
*/

/**
 * Demo de las placas con la disposicion exacta de la portada.
 *
 * Diferencia con page-demo.php: ahi las placas viven en un carrusel con su
 * propio namespace (.carrusel / .placa). Aca el markup es el de la seccion de
 * fechas de home.php -- .campus con .fecha adentro -- y los estilos son los
 * mismos de _campus.scss, para que la pagina se vea igual que la portada en
 * desktop y en mobile, con el mismo reacomodo por flex-wrap.
 *
 * Lo unico que se suma es la imagen de cada placa, que la portada no tiene:
 * entra como capa de fondo del .fecha (ver sass/_demo2.scss), asi que la caja
 * no cambia de tamanio.
 *
 * La portada no se toca: esto es una copia paralela.
 *
 * @package Campusmagnano
 */

get_header();

$fechas = new WP_Query(
	array(
		'post_type'      => 'event',
		'posts_per_page' => -1,
	)
);

$total = $fechas->found_posts;
?>
<main class="pagina-demo2">
<a class="whatsapp" target="_blank" rel="noopener" href="https://wa.me/+5493513281636?text=Hola!"></a>

<section>
	<h1 class="titulo-sitio"><?php bloginfo( 'name' ); ?></h1>

	<?php if ( ! $total ) : ?>

		<p>Todavia no hay fechas publicadas.</p>

	<?php else : ?>

	<div class="campus">

		<?php
		while ( $fechas->have_posts() ) :
			$fechas->the_post();

			$id        = get_the_ID();
			$permalink = get_permalink();
			$imagen_id = get_post_thumbnail_id( $id );

			// "Solo la imagen" (la opcion "Mostrar en la placa" de cada fecha):
			// para las placas cuyo texto ya viene dibujado adentro de la imagen.
			// El texto del sitio se esconde con display: none y la caja pasa a
			// medir por su cuenta: 50% de ancho y la proporcion fija de
			// aspect-ratio. Por eso estas placas van de a dos por fila y no
			// siguen el acomodo 3 + 2 del resto. Ver sass/_demo2.scss.
			$solo_imagen = $imagen_id && 'imagen' === cm_placa_modo( $id );

			// La portada agrega `unafecha` cuando hay una sola fecha, para
			// centrarla en vez de dejarla pegada a un costado.
			$clases = array( 'fecha' );
			if ( 1 === $total ) {
				$clases[] = 'unafecha';
			}
			if ( $imagen_id ) {
				$clases[] = 'fecha--con-imagen';
			}
			if ( $solo_imagen ) {
				$clases[] = 'fecha--solo-imagen';
			}

			$club     = get_post_meta( $id, 'event_club', true );
			$location = get_post_meta( $id, 'event_location', true );
			$date     = get_post_meta( $id, 'event_date', true );
			?>

			<div class="<?php echo esc_attr( implode( ' ', $clases ) ); ?>"
				<?php if ( $imagen_id ) : ?>
					style="--fecha-img:url('<?php echo esc_url( wp_get_attachment_image_url( $imagen_id, 'placa' ) ); ?>')"
				<?php endif; ?>
				>

				<?php
				// Link que cubre la placa entera. Hermano del boton y no
				// envolviendolo: un <a> dentro de otro <a> es HTML invalido.
				//
				// Con el texto a la vista se esconde del arbol de accesibilidad
				// (aria-hidden + tabindex -1) para no duplicar el enlace: el
				// nombre accesible lo da el boton "Participar". Cuando el texto
				// esta oculto ese boton no existe para un lector de pantalla, asi
				// que este pasa a ser el enlace de verdad y lleva el titulo como
				// nombre.
				?>
				<?php if ( $solo_imagen ) : ?>
					<a class="fecha__cover" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>"<?php echo cm_atributos_medicion( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya vienen escapados. ?>></a>
				<?php else : ?>
					<a class="fecha__cover" href="<?php echo esc_url( $permalink ); ?>" tabindex="-1" aria-hidden="true"<?php echo cm_atributos_medicion( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya vienen escapados. ?>></a>
				<?php endif; ?>

				<p class="titulo"><?php the_title(); ?></p>

				<?php if ( ! empty( $club ) ) : ?>
					<p class="titulo"><?php echo esc_html( $club ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $location ) ) : ?>
					<p class="city"><?php echo esc_html( $location ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $date ) ) : ?>
					<p class="date"><?php echo esc_html( $date ); ?></p>
				<?php endif; ?>

				<a class="btn quiero" href="<?php echo esc_url( $permalink ); ?>"<?php echo cm_atributos_medicion( $id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ya vienen escapados. ?>>Participar</a>
			</div>

			<?php
		endwhile;
		wp_reset_postdata();
		?>

	</div>

	<?php endif; ?>
</section>

</main>
<?php
get_footer();
