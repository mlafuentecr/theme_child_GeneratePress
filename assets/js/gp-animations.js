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
        var el     = entry.target;
        var repeat = el.hasAttribute('data-animate-repeat');

        if (entry.isIntersecting) {
          var delay = parseInt(el.dataset.animateDelay, 10) || 0;
          var dur   = el.dataset.animateDuration;

          if (dur) {
            el.style.transitionDuration = parseInt(dur, 10) + 'ms';
          }

          setTimeout(function () {
            el.classList.add('is-visible');
          }, delay);

          // Non-repeat: stop watching after first trigger.
          if (!repeat) {
            observer.unobserve(el);
          }
        } else if (repeat) {
          // Element left the viewport — reset so it animates again on re-entry.
          el.classList.remove('is-visible');
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  elements.forEach(function (el) {
    observer.observe(el);
  });
})();
