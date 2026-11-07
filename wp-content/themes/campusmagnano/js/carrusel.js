/**
 * Slider de placas.
 *
 * El desplazamiento lo hace el navegador: el viewport tiene overflow-x y
 * scroll-snap, asi que el swipe tactil, el momentum y el scroll con trackpad
 * son nativos. Este archivo solo mueve el scroll y mantiene los controles
 * sincronizados. Sin JS el track sigue siendo desplazable a mano.
 *
 * @package Campusmagnano
 */
( function () {
	'use strict';

	var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function initSlider( root ) {
		var viewport = root.querySelector( '[data-carrusel-viewport]' );
		var slides = root.querySelectorAll( '.carrusel__slide' );

		if ( ! viewport || slides.length < 2 ) {
			return;
		}

		var prev = root.querySelector( '[data-carrusel-prev]' );
		var next = root.querySelector( '[data-carrusel-next]' );
		var dots = Array.prototype.slice.call( root.querySelectorAll( '[data-carrusel-dot]' ) );

		var loop = root.dataset.loop === '1';
		var autoplay = root.dataset.autoplay === '1' && ! reduceMotion;
		var intervalo = ( parseInt( root.dataset.intervalo, 10 ) || 5 ) * 1000;

		var index = 0;
		var timer = null;

		function slideWidth() {
			return slides[ 0 ].getBoundingClientRect().width || 1;
		}

		/**
		 * Ultima posicion alcanzable.
		 *
		 * Con scroll-snap-align:start la ultima placa no puede quedar al
		 * principio del viewport: el navegador topa el scroll. Si no se
		 * descuentan las placas visibles, las flechas y los dots apuntan a
		 * posiciones que el scroll nunca va a tomar y el punto activo queda
		 * desfasado.
		 */
		function maxIndex() {
			var visibles = Math.round( viewport.clientWidth / slideWidth() ) || 1;
			return Math.max( 0, slides.length - visibles );
		}

		function currentIndex() {
			return Math.min( maxIndex(), Math.round( viewport.scrollLeft / slideWidth() ) );
		}

		/**
		 * @param {number}  i      Posicion destino.
		 * @param {boolean} manual Si la movio una persona (corta el autoplay).
		 * @param {boolean} wrap   Si puede dar la vuelta cuando `loop` esta
		 *                         activo. Solo lo pasan los avances de a un
		 *                         paso: un salto directo a una posicion fuera
		 *                         de rango topa, no da la vuelta.
		 */
		function goTo( i, manual, wrap ) {
			var max = maxIndex();

			if ( i > max ) {
				i = wrap && loop ? 0 : max;
			} else if ( i < 0 ) {
				i = wrap && loop ? max : 0;
			}

			index = i;
			viewport.scrollTo( { left: i * slideWidth() } );
			sync();

			if ( manual ) {
				detener();
			}
		}

		function sync() {
			var max = maxIndex();

			if ( prev ) {
				prev.disabled = ! loop && index <= 0;
				prev.setAttribute( 'aria-disabled', prev.disabled ? 'true' : 'false' );
			}
			if ( next ) {
				next.disabled = ! loop && index >= max;
				next.setAttribute( 'aria-disabled', next.disabled ? 'true' : 'false' );
			}

			dots.forEach( function ( dot, i ) {
				// Los dots que caen mas alla de la ultima posicion alcanzable
				// se esconden: su cantidad depende de cuantas placas se vean, y
				// eso cambia con el ancho de la pantalla.
				dot.hidden = i > max;
				dot.setAttribute( 'aria-selected', i === index ? 'true' : 'false' );
			} );
		}

		/* --- autoplay --- */

		function arrancar() {
			if ( ! autoplay || timer ) {
				return;
			}
			timer = window.setInterval( function () {
				var max = maxIndex();
				if ( ! loop && index >= max ) {
					detener();
					return;
				}
				goTo( index + 1, false, true );
			}, intervalo );
		}

		function detener() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		/* --- eventos --- */

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				goTo( index - 1, true, true );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				goTo( index + 1, true, true );
			} );
		}

		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				goTo( parseInt( dot.dataset.carruselDot, 10 ) || 0, true );
			} );
		} );

		var scrollTimer = null;
		viewport.addEventListener(
			'scroll',
			function () {
				window.clearTimeout( scrollTimer );
				scrollTimer = window.setTimeout( function () {
					index = currentIndex();
					sync();
				}, 100 );
			},
			{ passive: true }
		);

		root.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'ArrowLeft' ) {
				goTo( index - 1, true, true );
			} else if ( e.key === 'ArrowRight' ) {
				goTo( index + 1, true, true );
			} else if ( e.key === 'Home' ) {
				goTo( 0, true );
			} else if ( e.key === 'End' ) {
				goTo( maxIndex(), true );
			} else {
				return;
			}
			e.preventDefault();
		} );

		// Cualquier intencion de interactuar corta el movimiento automatico y
		// no lo reanuda: retomarlo mientras alguien esta leyendo una placa es
		// peor que no tenerlo.
		[ 'pointerdown', 'pointerenter', 'focusin', 'wheel', 'touchstart' ].forEach( function ( evento ) {
			root.addEventListener( evento, detener, { passive: true, once: true } );
		} );

		var resizeTimer = null;
		window.addEventListener( 'resize', function () {
			window.clearTimeout( resizeTimer );
			resizeTimer = window.setTimeout( function () {
				index = currentIndex();
				sync();
			}, 150 );
		} );

		sync();
		arrancar();
	}

	function init() {
		Array.prototype.forEach.call( document.querySelectorAll( '[data-carrusel]' ), initSlider );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
