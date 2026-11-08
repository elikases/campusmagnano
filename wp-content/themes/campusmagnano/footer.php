<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Campusmagnano
 */

?>
    <footer>
        <div class="wrap">
            <a href="#" class="logo"></a>
            <small>

Consultas e información: Franco Zecchin · +54 9 351 328-1636

</small>
<!--             <small>Copyright © 2024 campusmagnano.com. Franco Zecchin: +54 9 351 328-1636</small> -->
            <div class="social">
                <a class="x" href="https://x.com/CampusMagnano?lang=es&mx=2" target="_blank"></a>
                <a class="facebook" href="https://www.facebook.com/campus.magnano/" target="_blank"></a>
                <a class="instagram" href="https://www.instagram.com/campusmagnano/" target="_blank"></a>
            </div>
        </div>
    </footer>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
<script>
    // Selecciona el interruptor
    const themeSwitch = document.getElementById('themeSwitch');

    // En la pagina del demo el switch no se renderiza. Sin esta guarda,
    // addEventListener sobre null tira un TypeError y ensucia la consola.
    if (themeSwitch) {
        // Verifica el estado del switch cuando se carga la página
        document.addEventListener('DOMContentLoaded', () => {
            // Comprueba si el body tiene la clase 'dark' al cargar la página
            if (document.body.classList.contains('dark')) {
                themeSwitch.checked = true;
            }
        });

        // Agrega un evento de cambio al switch
        themeSwitch.addEventListener('change', () => {
            // Alterna la clase 'dark' en el body
            document.body.classList.toggle('dark');
        });
    }
</script>
