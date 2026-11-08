<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Campusmagnano
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<?php if ( 'production' === wp_get_environment_type() ) : ?>
    <!-- Facebook Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window,document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
     fbq('init', '709047704796888');
    fbq('track', 'PageView');
    </script>
    <noscript>
     <img height="1" width="1"
    src="https://www.facebook.com/tr?id=709047704796888&ev=PageView
    &noscript=1"/>
    </noscript>
    <!-- End Facebook Pixel Code -->
<?php endif; ?>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--<link rel="profile" href="https://gmpg.org/xfn/11">-->
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/css/styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel='shortcut icon' type='image/x-icon' href='<?php echo get_stylesheet_directory_uri(); ?>/images/i-magnano.svg' />
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>




    <header>
        <div class="wrap">
            <a href="/" class="logo2"></a>
            <?php
            // El switch de tema oscuro no se muestra en la portada ni en la
            // pagina del demo. Sigue apareciendo en el resto de las plantillas
            // (fechas, paginas sueltas, archivos, busqueda, 404).
            $cm_ocultar_switch = is_front_page() || is_home()
                || is_page_template( 'page-demo.php' ) || is_page( 'demo' );
            if ( ! $cm_ocultar_switch ) :
                ?>
            <label class="switch">
                <input type="checkbox" id="themeSwitch">
                <span class="slider round"></span>
            </label>
            <?php endif; ?>
        </div>
    </header>
