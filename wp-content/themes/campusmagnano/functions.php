<?php
/**
 * Campusmagnano functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Campusmagnano
 */

if ( ! defined( '_S_VERSION' ) ) {
    // Replace the version number of the theme on each release.
    define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function campusmagnano_setup() {
    /*
        * Make theme available for translation.
        * Translations can be filed in the /languages/ directory.
        * If you're building a theme based on Campusmagnano, use a find and replace
        * to change 'campusmagnano' to the name of your theme in all the template files.
        */
    load_theme_textdomain( 'campusmagnano', get_template_directory() . '/languages' );

    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    /*
        * Let WordPress manage the document title.
        * By adding theme support, we declare that this theme does not use a
        * hard-coded <title> tag in the document head, and expect WordPress to
        * provide it for us.
        */
    add_theme_support( 'title-tag' );

    /*
        * Enable support for Post Thumbnails on posts and pages.
        *
        * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
        */
    add_theme_support( 'post-thumbnails' );

    // This theme uses wp_nav_menu() in one location.
    register_nav_menus(
        array(
            'menu-1' => esc_html__( 'Primary', 'campusmagnano' ),
        )
    );

    /*
        * Switch default core markup for search form, comment form, and comments
        * to output valid HTML5.
        */
    add_theme_support(
        'html5',
        array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        )
    );

    // Set up the WordPress core custom background feature.
    add_theme_support(
        'custom-background',
        apply_filters(
            'campusmagnano_custom_background_args',
            array(
                'default-color' => 'ffffff',
                'default-image' => '',
            )
        )
    );

    // Add theme support for selective refresh for widgets.
    add_theme_support( 'customize-selective-refresh-widgets' );

    /**
     * Add support for core custom logo.
     *
     * @link https://codex.wordpress.org/Theme_Logo
     */
    add_theme_support(
        'custom-logo',
        array(
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
        )
    );
}
add_action( 'after_setup_theme', 'campusmagnano_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function campusmagnano_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'campusmagnano_content_width', 640 );
}
add_action( 'after_setup_theme', 'campusmagnano_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function campusmagnano_widgets_init() {
    register_sidebar(
        array(
            'name'          => esc_html__( 'Sidebar', 'campusmagnano' ),
            'id'            => 'sidebar-1',
            'description'   => esc_html__( 'Add widgets here.', 'campusmagnano' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );
}
add_action( 'widgets_init', 'campusmagnano_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function campusmagnano_scripts() {
    wp_enqueue_style( 'campusmagnano-style', get_stylesheet_uri(), array(), _S_VERSION );
    wp_style_add_data( 'campusmagnano-style', 'rtl', 'replace' );

    wp_enqueue_script( 'campusmagnano-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'campusmagnano_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
    require get_template_directory() . '/inc/jetpack.php';
}



function create_custom_post_type() {
    register_post_type('event',
        array(
            'labels' => array(
                'name' => __('Fechas'),
                'singular_name' => __('Event')
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor'),
            'rewrite' => array('slug' => 'fecha'), // Cambia 'nueva-url' por el slug que desees
        )
    );
}
add_action('init', 'create_custom_post_type');

function add_event_meta_boxes() {
    add_meta_box('event_details', 'Detalles del Campus', 'event_details_callback', 'event', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_event_meta_boxes');

function event_details_callback($post) {
    $location = get_post_meta($post->ID, 'event_location', true);
    $date = get_post_meta($post->ID, 'event_date', true);
    $club = get_post_meta($post->ID, 'event_club', true);
    $iframe = get_post_meta($post->ID, 'event_iframe', true);
    ?>
    <div style="display:flex; flex-direction:column; gap:10px">
        <div style="display:flex; gap:10px; text-align: right; align-items:center">
            <label style="display:block; width:100px" for="event_location">Ciudad:</label>
            <input type="text" id="event_location" name="event_location" value="<?php echo esc_attr($location); ?>" />
        </div>
        <div style="display:flex; gap:10px; text-align: right; align-items:center">
            <label style="display:block; width:100px" for="event_date">Fecha:</label>
            <input type="text" id="event_date" name="event_date" value="<?php echo esc_attr($date); ?>" />
        </div>
        <div style="display:flex; gap:10px; text-align: right; align-items:center">
            <label style="display:block; width:100px" for="event_club">Club:</label>
            <input type="text" id="event_club" name="event_club" value="<?php echo esc_attr($club); ?>" />
        </div>
        <div style="display:flex; gap:10px; text-align: right; align-items:center">
            <label style="display:block; width:100px" for="event_iframe">Formulario:</label>
            <textarea id="event_iframe" name="event_iframe"><?php echo esc_textarea($iframe); ?></textarea>
        </div>
    </div>
    <?php
}

function save_event_meta_boxes($post_id) {
    if (array_key_exists('event_location', $_POST)) {
        update_post_meta($post_id, 'event_location', $_POST['event_location']);
    }
    if (array_key_exists('event_date', $_POST)) {
        update_post_meta($post_id, 'event_date', $_POST['event_date']);
    }
    if (array_key_exists('event_club', $_POST)) {
        update_post_meta($post_id, 'event_club', $_POST['event_club']);
    }
    if (array_key_exists('event_iframe', $_POST)) {
        update_post_meta($post_id, 'event_iframe', $_POST['event_iframe']);
    }
}
add_action('save_post', 'save_event_meta_boxes');


function crear_post_type_caracteristicas() {
    $args = array(
        'label' => 'Características',
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'), // Personaliza los soportes según lo que necesites
        'rewrite' => array('slug' => 'caracteristicas'),
    );

    register_post_type('caracteristicas', $args);
}

add_action('init', 'crear_post_type_caracteristicas');

//remove_filter('the_content', 'wpautop');

function crear_post_type_coachs() {
    $args = array(
        'label' => 'Coachs',
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'), // Personaliza los soportes según lo que necesites
        'rewrite' => array('slug' => 'coachs'),
    );

    register_post_type('coachs', $args);
}

add_action('init', 'crear_post_type_coachs');

function crear_post_type_modalidades() {
    $args = array(
        'label' => 'Modalidades',
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'rewrite' => array('slug' => 'modalidades'),
    );

    register_post_type('modalidades', $args);
}
add_action('init', 'crear_post_type_modalidades');

function agregar_meta_box_modalidades() {
    add_meta_box(
        'modalidad_options',
        'Opciones de Modalidad',
        'mostrar_meta_box_modalidades',
        'modalidades',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'agregar_meta_box_modalidades');

function mostrar_meta_box_modalidades($post) {
    wp_nonce_field(basename(__FILE__), 'modalidad_nonce');

    // Recuperar los valores actuales
    $valores = get_post_meta($post->ID, '_modalidad_options', true);
    $valores = !empty($valores) ? (array) $valores : [];
    ?>
    <label>
        <input type="checkbox" name="modalidad_options[]" value="sin_pension" <?php checked(in_array('sin_pension', $valores)); ?> /> Sin Pensión
    </label><br />
    <label>
        <input type="checkbox" name="modalidad_options[]" value="con_pension" <?php checked(in_array('con_pension', $valores)); ?> /> Con Pensión
    </label><br />
    <label>
        <input type="checkbox" name="modalidad_options[]" value="media_pension" <?php checked(in_array('media_pension', $valores)); ?> /> Media Pensión
    </label>
    <?php
}

function guardar_meta_box_modalidades($post_id) {
    if (!isset($_POST['modalidad_nonce']) || !wp_verify_nonce($_POST['modalidad_nonce'], basename(__FILE__))) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    // Guardar las opciones seleccionadas
    if (isset($_POST['modalidad_options'])) {
        $options = array_map('sanitize_text_field', $_POST['modalidad_options']);
        update_post_meta($post_id, '_modalidad_options', $options);
    } else {
        delete_post_meta($post_id, '_modalidad_options'); // Eliminar si no hay opciones seleccionadas
    }
}
add_action('save_post', 'guardar_meta_box_modalidades');



// Enqueue your script
function my_enqueue_script() {
    wp_enqueue_script('my-script', get_template_directory_uri() . '/js/my-script.js', array('jquery'), null, true);

    // Localize the script with new data
    $post_permalink = get_permalink();
    wp_localize_script('my-script', 'myScriptData', array(
        'permalink' => $post_permalink,
    ));
}
add_action('wp_enqueue_scripts', 'my_enqueue_script');
