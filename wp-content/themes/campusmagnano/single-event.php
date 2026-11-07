<?php
/**
 * The template for displaying all single posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package Campusmagnano
 */

get_header();
?>

    <main id="primary" class="site-main">

    <h1><?php the_title(); ?></h1>

    <!--
    <p><strong>Location:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'event_location', true)); ?></p>
    <p><strong>Fecha</strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'event_date', true)); ?></p>
    <p><strong>Club:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'event_club', true)); ?></p>
    -->

    <style>
        .iframe-container {
            display:flex;
            justify-content: center;
        }


    </style>
    <div class="iframe-container">
        <?php echo get_post_meta(get_the_ID(), 'event_iframe', true); ?>
    </div>


    </main><!-- #main -->

<?php

get_footer();
