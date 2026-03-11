/**
 * GP Parallax — lightweight, RAF-throttled parallax scroll effect.
 *
 * Modo A — elemento con imagen de fondo (class "parallax-bg"):
 *   Mueve background-position-y. Ideal para divs de GenerateBlocks con bg-image.
 *   Atributo opcional: data-parallax-speed  (default 0.3)
 *
 * Modo B — elemento tipo <img> o div hijo (atributo "data-parallax"):
 *   Aplica translate3d al elemento. Requiere .parallax-container con overflow:hidden.
 *   Atributos: data-parallax-speed | data-parallax-axis | data-parallax-direction
 */
(function () {
	'use strict';

	// Bail si el usuario prefiere reducir movimiento
	if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
		return;
	}

	var allEls = Array.prototype.slice.call(document.querySelectorAll('[data-parallax], .parallax-bg'));
	if (!allEls.length) return;

	// Separar en dos grupos según si tienen background-image
	var bgEls = [];
	var transformEls = [];

	allEls.forEach(function (el) {
		var hasBgImage = window.getComputedStyle(el).backgroundImage !== 'none';
		if (hasBgImage) {
			bgEls.push(el);
		} else if (el.classList.contains('parallax-bg')) {
			// Sin CSS background-image: buscar figura/img hijo y usar modo transform
			var imgChild = el.querySelector('figure, img');
			if (imgChild) {
				imgChild.dataset.parallaxSpeed = el.dataset.parallaxSpeed || '0.3';
				el.style.overflow = 'hidden';
				transformEls.push(imgChild);
			}
			// Si no hay img hijo tampoco hay nada que animar
		} else {
			transformEls.push(el);
		}
	});

	var ticking = false;

	function update() {
		var vhalf = window.innerHeight / 2;

		// ── Modo A: background-position-y ────────────────────────────────────────
		bgEls.forEach(function (el) {
			var rect = el.getBoundingClientRect();
			var center = rect.top + rect.height / 2;
			var offset = center - vhalf; // relativo al viewport
			var speed = parseFloat(el.dataset.parallaxSpeed || '0.3');
			el.style.backgroundPosition = 'center calc(50% + ' + offset * speed * 0.4 + 'px)';
		});

		// ── Modo B: translate3d ───────────────────────────────────────────────────
		var scrollY = window.pageYOffset || document.documentElement.scrollTop;
		transformEls.forEach(function (el) {
			var rect = el.getBoundingClientRect();
			var center = rect.top + rect.height / 2;
			var offset = center - vhalf + scrollY;
			var speed = parseFloat(el.dataset.parallaxSpeed || '0.3');
			var axis = (el.dataset.parallaxAxis || 'y').toLowerCase();
			var direction = parseFloat(el.dataset.parallaxDirection || '1');
			var value = offset * speed * direction * -1;

			el.style.transform = axis === 'x' ? 'translate3d(' + value + 'px, 0, 0)' : 'translate3d(0, ' + value + 'px, 0)';
		});

		ticking = false;
	}

	function onScroll() {
		if (!ticking) {
			requestAnimationFrame(update);
			ticking = true;
		}
	}

	window.addEventListener('scroll', onScroll, { passive: true });
	window.addEventListener('resize', update, { passive: true });

	// Corre al inicio y de nuevo cuando todas las imágenes hayan cargado
	update();
	window.addEventListener('load', update, { passive: true });
})();
