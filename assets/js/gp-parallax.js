/**
 * GP Parallax — lightweight, RAF-throttled parallax scroll effect.
 *
 * Attributes:
 *   data-parallax             — marks the element (required)
 *   data-parallax-speed       — float, default 0.3  (0 = no movement, 1 = full scroll speed)
 *   data-parallax-axis        — "y" (default) | "x"
 *   data-parallax-direction   — "1" (default, moves opposite to scroll) | "-1" (same direction)
 */
(function () {
  'use strict';

  // Bail if reduced-motion is preferred
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  var els = Array.prototype.slice.call(document.querySelectorAll('[data-parallax]'));
  if (!els.length) return;

  var ticking = false;

  function update() {
    var scrollY = window.pageYOffset || document.documentElement.scrollTop;
    var vhalf   = window.innerHeight / 2;

    els.forEach(function (el) {
      var rect   = el.getBoundingClientRect();
      var center = rect.top + rect.height / 2;
      var offset = (center - vhalf + scrollY);

      var speed     = parseFloat(el.dataset.parallaxSpeed     || '0.3');
      var axis      = (el.dataset.parallaxAxis                || 'y').toLowerCase();
      var direction = parseFloat(el.dataset.parallaxDirection || '1');
      var value     = offset * speed * direction * -1;

      el.style.transform = axis === 'x'
        ? 'translate3d(' + value + 'px, 0, 0)'
        : 'translate3d(0, ' + value + 'px, 0)';
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
  update();
})();
