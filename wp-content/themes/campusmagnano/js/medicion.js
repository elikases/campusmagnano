/**
 * Mide en el pixel de Meta el clic en cada fecha.
 *
 * Cada enlace que lleva a la pagina de una fecha trae `data-campus` y
 * `data-campus-id` (los pone cm_atributos_medicion(), en inc/placas.php). Este
 * script escucha el clic y manda a Meta un evento InitiateCheckout con el
 * nombre del campus adentro, que es lo que despues permite abrir el informe
 * por campus y armar publicos de remarketing de cada uno por separado.
 *
 * Si no hay pixel -- en local esta apagado a proposito, y en produccion puede
 * estar bloqueado por una extension del visitante -- el script no hace nada y
 * los enlaces siguen funcionando igual.
 *
 * @package Campusmagnano
 */

( function () {
	'use strict';

	// Cuanto se espera antes de salir de la pagina, para darle tiempo al
	// pedido del pixel a salir. El navegador cancela las peticiones de una
	// pagina que se esta yendo, y el evento se perderia justo en el clic que
	// mas interesa medir.
	var ESPERA = 250;

	/**
	 * Los datos que viajan a Meta.
	 *
	 * content_name es lo que se ve en el desglose del Administrador de eventos.
	 * content_ids lleva el ID de la fecha en WordPress, que es lo unico que no
	 * se repite nunca: dos campus pueden llamarse igual, el ID no.
	 */
	function datosDelCampus( enlace ) {
		var datos = {
			content_name: enlace.getAttribute( 'data-campus' ) || '',
			content_category: 'campus',
			content_type: 'product'
		};

		var id = enlace.getAttribute( 'data-campus-id' );
		if ( id ) {
			datos.content_ids = [ id ];
		}

		return datos;
	}

	document.addEventListener( 'click', function ( evento ) {
		if ( ! evento.target || ! evento.target.closest ) {
			return;
		}

		var enlace = evento.target.closest( '[data-campus]' );
		if ( ! enlace ) {
			return;
		}

		// Sin pixel no hay nada que medir y, sobre todo, nada que esperar: el
		// enlace tiene que andar como si este script no existiera.
		if ( 'function' !== typeof window.fbq ) {
			return;
		}

		try {
			window.fbq( 'track', 'InitiateCheckout', datosDelCampus( enlace ) );
		} catch ( e ) {
			// Un error midiendo no puede dejar el boton muerto.
			return;
		}

		// Con Cmd, Ctrl, Shift o Alt el navegador abre otra pestania o descarga:
		// esta pagina no se va a ningun lado y el pedido sale tranquilo.
		var abreAparte = evento.metaKey || evento.ctrlKey || evento.shiftKey ||
			evento.altKey || '_blank' === enlace.target;

		if ( abreAparte || ! enlace.href ) {
			return;
		}

		evento.preventDefault();

		var navegado = false;
		var ir = function () {
			if ( navegado ) {
				return;
			}
			navegado = true;
			window.location.href = enlace.href;
		};

		window.setTimeout( ir, ESPERA );
	} );
}() );
