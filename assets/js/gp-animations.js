/**
 * GP Animations — IntersectionObserver-based scroll entrance animations.
 * Reads data-animate, data-animate-delay (ms), data-animate-duration (ms).
 */
(function () {
  'use strict';

  if (typeof IntersectionObserver === 'undefined') {
    // Fallback: make all elements visible immediately
    document.querySelectorAll('[data-animate]').forEach(function (el) {
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

  document.querySelectorAll('[data-animate]').forEach(function (el) {
    observer.observe(el);
  });
})();
