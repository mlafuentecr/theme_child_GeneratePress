/**
 * GP Animations — IntersectionObserver-based scroll entrance animations.
 * Targets elements with classes: fade-up | fade-down | fade-left | fade-right | zoom-in | zoom-out
 * Optional: data-animate-delay="200" and data-animate-duration="1000"
 */
(function () {
  'use strict';

  var SELECTOR = '.fade-up, .fade-down, .fade-left, .fade-right, .zoom-in, .zoom-out';

  var elements = document.querySelectorAll(SELECTOR);

  if (!elements.length) return;

  // Fallback for browsers without IntersectionObserver
  if (typeof IntersectionObserver === 'undefined') {
    elements.forEach(function (el) {
      el.classList.add('is-visible');
    });
    return;
  }

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;

        var el    = entry.target;
        var delay = parseInt(el.dataset.animateDelay, 10) || 0;
        var dur   = el.dataset.animateDuration;

        if (dur) {
          el.style.transitionDuration = parseInt(dur, 10) + 'ms';
        }

        setTimeout(function () {
          el.classList.add('is-visible');
        }, delay);

        observer.unobserve(el);
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  elements.forEach(function (el) {
    observer.observe(el);
  });
})();
