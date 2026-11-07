<?php
/*
Template Name: Página de Inicio
*/
get_header(); // Incluye el encabezado del tema
?>
<!-- Aquí va el contenido personalizado de tu página de inicio -->
<main>
<a class="whatsapp" target="_blank" rel="noopener" href="https://wa.me/+5493513281636?text=Hola!"></a>

<style>
    .unafecha {
        align-items:center !important;
    }
</style>
<section>
    <h1><?php bloginfo('name'); ?></h1>
    <div class="campus">

    <?php
    $loop = new WP_Query(array('post_type' => 'event', 'posts_per_page' => -1, 'category' => 'current'));
    $total_posts = $loop->found_posts; // Contar el número total de posts
    ?>

    <?php while ($loop->have_posts()) : $loop->the_post(); ?>

        <?php
        $location = get_post_meta($post->ID, 'event_location', true);
        $date = get_post_meta($post->ID, 'event_date', true);
        $club = get_post_meta($post->ID, 'event_club', true);

        // Obtener el enlace al artículo
        $enlace_articulo = get_permalink($post->ID);

        // Comenzar a crear el contenedor con la clase según el número de artículos
        $clase = ($total_posts === 1) ? 'fecha unafecha' : 'fecha';
        echo '<div class="' . esc_attr($clase) . '">';

        ?>

        <p class="titulo"><?php echo get_the_title(); ?></p>

        <?php
        if (!empty($club)) {
            echo '<p class="titulo">' . esc_html($club) . '</p>';
        }

        if (!empty($location)) {
            echo '<p class="city">' . esc_html($location) . '</p>';
        }

        if (!empty($date)) {
            echo '<p class="date">' . esc_html($date) . '</p>';
        }

        // Agregar el enlace "Participar"
        echo '<a class="btn quiero" href="' . esc_url($enlace_articulo) . '">Participar</a>';

        echo '</div>';
        ?>

    <?php endwhile; wp_reset_query(); ?>

    </div>
</section>

<section>
    <div class="wrap">
        <h2>En Que consiste nuestro Campus</h2>
        <div class="acordeon">

        <?php
        $args = array(
            'post_type' => 'caracteristicas',
            'posts_per_page' => 5, // Cambia el número según lo que necesites
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {

            while ($query->have_posts()) {
                $query->the_post();
                ?>
                <details>
                    <summary>
                        <span>
                            <?php the_title(); ?>
                        </span>
                        </summary>
                    <?php the_content(); ?>
                </details>
                <?php
            }

            wp_reset_postdata();
        } else {
            echo '';
        }
        ?>


            <details>
                <summary>
                    <span>
                        Staff de Coachs
                    </span>
                </summary>
                <div class="coach">
                <h3>Un equipo formado por coachs de alto nivel</h3>
                <ul>
                    <?php
                    $args = array(
                        'post_type' => 'coachs',
                        'posts_per_page' => 5, // Cambia el número según lo que necesites
                    );

                    $query = new WP_Query($args);

                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            // Recuperamos el valor del meta
                            $modalidad_option = get_post_meta(get_the_ID(), '_modalidad_option', true);
                            ?>


                                <li><?php the_title(); ?></li>


                            <?php
                        }

                        wp_reset_postdata();
                    } else {
                        //echo '<p>No hay características disponibles.</p>';
                    }
                    ?>
                     </ul>
                    </div>
                </details>



            <details>
                <summary>
                    <span>
                        Modalidades de participación
                    </span>
                </summary>

                <div class="modalidades" style="grid-template-columns: 1fr min-content min-content min-content">
    <div class="row">
        <p></p>
        <span style="text-align:center">Pensión Completa</span>
        <span style="text-align:center">Media Pensión</span>
        <span style="text-align:center">Sin Pensión</span>
    </div>

    <?php
    $args = array(
        'post_type' => 'modalidades',
        'posts_per_page' => 15,
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $modalidad_options = get_post_meta(get_the_ID(), '_modalidad_options', true);
            $modalidad_options = !empty($modalidad_options) ? (array) $modalidad_options : [];
            ?>

            <div class="row">
                <p style="text-align:left"><?php the_title(); ?></p>
                <span class="<?php echo in_array('con_pension', $modalidad_options) ? 'si' : 'no'; ?>"></span>
                <span class="<?php echo in_array('media_pension', $modalidad_options) ? 'si' : 'no'; ?>"></span>
                <span class="<?php echo in_array('sin_pension', $modalidad_options) ? 'si' : 'no'; ?>"></span>
            </div>

            <?php
        }
        wp_reset_postdata();
    } else {
        echo '<p>No hay modalidades disponibles.</p>';
    }
    ?>
</div>

                </details>

            </div>
        </div>
    </section>

</main>





<?php
get_footer(); // Incluye el pie de página del tema
?>
