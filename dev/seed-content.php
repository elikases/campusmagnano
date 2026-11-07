<?php
/**
 * Siembra el contenido de desarrollo del theme Campus Magnano.
 *
 * Los datos son los del sitio en produccion (campusmagnano.com), tomados de una
 * exportacion WXR. Se cargan asi en vez de importar el XML para dejar fuera los
 * comentarios de spam, los items de menu huerfanos y las fechas en borrador que
 * el export arrastra y que no renderizan en ninguna plantilla.
 *
 * Es idempotente: si una entrada ya existe (match por slug) no la duplica.
 *
 * Uso:
 *   docker compose exec -T wpcli wp eval-file /var/www/html/dev/seed-content.php
 *
 * @package Campusmagnano
 */

function cm_seed( $tipo, $titulo, $slug, $fecha, $contenido = '', $metas = array() ) {
	$existente = get_page_by_path( $slug, OBJECT, $tipo );
	if ( $existente ) {
		echo "  ya existe: $titulo\n";
		return $existente->ID;
	}
	$id = wp_insert_post(
		array(
			'post_type'      => $tipo,
			'post_title'     => $titulo,
			'post_name'      => $slug,
			'post_content'   => $contenido,
			'post_status'    => 'publish',
			'post_date'      => $fecha,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		echo "  ERROR en $titulo: " . $id->get_error_message() . "\n";
		return 0;
	}
	foreach ( $metas as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	echo "  creado [$tipo] $titulo (ID $id)\n";
	return $id;
}

function cm_seed_iframe( $id_form, $alto ) {
	return '<iframe src="https://docs.google.com/forms/d/e/' . $id_form . '/viewform?embedded=true" width="640" height="' . $alto . '" frameborder="0" marginheight="0" marginwidth="0">Cargando…</iframe>';
}

echo "== caracteristicas (acordeon de la home) ==\n";

cm_seed(
	'caracteristicas',
	'Días a puro Basketball',
	'5-dias-a-puro-basket',
	'2024-10-07 17:08:22',
	"Donde los niños y adolecentes se enriquesen de experiencias, conocimientos y buenos habitos para la practica deportiva guiados por un staff de profesionales del deporte.\n\nDurante los entrenamientos buscamos potenciar la versatilidad del jugadores nutriendolos de conocimientos tecnicos ,tacticos y psicologicos.\n\nEl objetivo del Campus Magnano es lograr que los campistas pueda trasladar el aprendizaje a su practica diaria en sus respectivos clubes."
);

cm_seed(
	'caracteristicas',
	'Metodología de Trabajo',
	'metodologia-de-trabajo',
	'2024-10-07 17:08:47',
	"El campus consiste en un entrenamiento que se realiza en forma paralela y coordinada en 6 complejos deportivos diferentes. Allí se encuentran los campistas con su grupo correspondiente de trabajo, categorizado por edad y nivel de juego.\n\nEn el campus se entrenan dos turnos diarios, uno durante la mañana y otro por la tarde.\n\nCada uno de estos turnos se divide en módulos de cincuenta minutos cada uno. Entre ellos disponemos de diez minutos de pausa. Los aprovechamos para hidratarnos y trasladarnos a otra estación de trabajo e iniciar un nuevo módulo.\n\nEs decir, que los alumnos entrenan intensamente seis horas diarias (aproximadamente).\n\nLos campistas tendrán la oportunidad de capacitarse y entrenar entre tres y cuatro módulos con cada uno de los profesionales, en donde van a trabajar un aspecto específico del juego con cada uno de ellos:\n<div class=\"metodologia\">\n<ul>\n     <li>Entrenamiento Cognitivo</li>\n     <li>Toma de decisiones</li>\n     <li>Dribling</li>\n     <li>Lanzamiento</li>\n     <li>Definiciones</li>\n</ul>\n</div>"
);

echo "== coachs (staff) ==\n";

cm_seed( 'coachs', 'Ezequiel Berguero', 'ezequiel-berguero', '2024-10-07 17:09:28' );
cm_seed( 'coachs', 'Juan Pablo Caro', 'juan-pablo-caro', '2024-10-07 17:09:39' );
cm_seed( 'coachs', 'Martin “Polo” López', 'martin-polo-lopez', '2024-10-07 17:09:53' );
cm_seed( 'coachs', 'Ricardo Bojanich', 'ricardo-bojanich', '2024-10-07 17:10:02' );

echo "== modalidades (tabla de pensiones) ==\n";

$todas  = array( 'sin_pension', 'con_pension', 'media_pension' );
$con    = array( 'con_pension' );
$con_me = array( 'con_pension', 'media_pension' );

cm_seed( 'modalidades', 'Diploma', 'diploma', '2024-10-07 17:12:00', '', array( '_modalidad_options' => $todas ) );
cm_seed( 'modalidades', 'Seguro Medico', 'seguro-medico', '2024-10-07 17:12:14', '', array( '_modalidad_options' => $todas ) );
cm_seed( 'modalidades', 'Evaluación', 'evaluacion', '2024-10-07 17:12:32', '', array( '_modalidad_options' => $todas ) );
cm_seed( 'modalidades', 'Camisetas 2025', 'camisetas-2024', '2024-10-07 17:12:58', '', array( '_modalidad_options' => $todas ) );
cm_seed( 'modalidades', 'Cena', 'cena', '2024-10-07 17:13:08', '', array( '_modalidad_options' => $con ) );
cm_seed( 'modalidades', 'Merienda', 'merienda', '2024-10-07 17:13:20', '', array( '_modalidad_options' => $todas ) );
cm_seed( 'modalidades', 'Almuerzo', 'almuerzo', '2024-10-07 17:13:25', '', array( '_modalidad_options' => $con_me ) );
cm_seed( 'modalidades', 'Traslados', 'traslados', '2024-10-07 17:13:38', '', array( '_modalidad_options' => $con ) );
cm_seed( 'modalidades', 'Desayuno', 'desayuno', '2024-10-07 17:13:45', '', array( '_modalidad_options' => $con ) );

echo "== fechas (CPT event) ==\n";

cm_seed(
	'event',
	'Campus Justiniano Posse',
	'campus-justiniano-pose',
	'2026-08-12 14:39:09',
	'',
	array(
		'event_location' => 'Justiniano Posse - Córdoba',
		'event_date'     => '18, 19 y 20 de Diciembre',
		'event_club'     => 'Complejo Justiniano Posse',
		'event_iframe'   => cm_seed_iframe( '1FAIpQLSdHLgtHDm0h977X4MAo-uERDqVBBIb_E_CnzBmH7Iy7uXAf4w', 2770 ),
	)
);

cm_seed(
	'event',
	'TALLER DE TIRO',
	'taller-de-tiro-diciembre-2026',
	'2026-10-28 21:20:46',
	'',
	array(
		'event_location' => 'DICIEMBRE 2026 - Córdoba',
		'event_date'     => '05, 06 y 07 de Diciembre',
		'event_club'     => 'COLEGIO CASTEL FRANCO',
		'event_iframe'   => cm_seed_iframe( '1FAIpQLSe9W0cmFRfxXFcdQLZswfbrZAs_-Au8hdPe_G8p3EfjGdmaEQ', 2730 ),
	)
);

cm_seed(
	'event',
	'SUMMER CAMP',
	'summer-camp',
	'2026-11-06 14:23:36',
	'',
	array(
		'event_location' => 'Las Grutas - Río Negro',
		'event_date'     => '20, 21, 22 y 23 de Enero',
		'event_club'     => 'Polideportivo Municipal',
		'event_iframe'   => cm_seed_iframe( '1FAIpQLSeUMU0aPk8b6ArhzzA8NdMkHPMR4s6Xmf3NfakDBYHB3IO2rA', 2690 ),
	)
);

cm_seed(
	'event',
	'SUMMER CAMP - RÍO SEGUNDO',
	'summer-camp-rio-segundo',
	'2026-11-06 14:35:47',
	'',
	array(
		'event_location' => 'Río Segundo, Córdoba',
		'event_date'     => '27, 28, 29 y 30 de Enero',
		'event_club'     => 'EL NORTE - Río Segundo',
		'event_iframe'   => cm_seed_iframe( '1FAIpQLSd_Vs_64fl96VrvmdZEw19R3jU4SOcUjlfuFeokAf79xN6gpw', 2770 ),
	)
);

cm_seed(
	'event',
	'SUMMER CAMP - CÓRDOBA',
	'summer-camp-cordoba',
	'2026-11-06 14:45:09',
	'',
	array(
		'event_location' => 'Córdoba',
		'event_date'     => '06, 07, 08 Y 09 de Enero',
		'event_club'     => 'COLEGIO CASTELFRANCO',
		'event_iframe'   => cm_seed_iframe( '1FAIpQLSfMfex67C4fM17ejPYPXar1yzilvyJexlJIXNvgw5yYNVqi5Q', 2690 ),
	)
);

echo "LISTO\n";
