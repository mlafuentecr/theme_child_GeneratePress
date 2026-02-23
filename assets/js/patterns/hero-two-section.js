/* Pattern: hero-two-section
   Handles: parallax on media panel (subtle, respects prefers-reduced-motion)
            smooth scroll on scroll indicator click.
   ------------------------------------------------------------------ */
(function () {
  'use strict';

  var hero  = document.querySelector('.hero-two-section');
  if (!hero) return;

  var media  = hero.querySelector('.hero-two-section__media');
  var scroll = hero.querySelector('.hero-two-section__scroll');

  // ── Parallax on media panel ──────────────────────────────────────────────────
  var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (media && !prefersReduced) {
    var ticking = false;

    function onScroll() {
      if (!ticking) {
        window.requestAnimationFrame(function () {
          var rect   = hero.getBoundingClientRect();
          var ratio  = rect.top / window.innerHeight;        // –1 … 1
          var offset = Math.round(ratio * 40);               // max 40px shift
          media.style.transform = 'translateY(' + offset + 'px)';
          ticking = false;
        });
        ticking = true;
      }
    }

    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // ── Scroll indicator ─────────────────────────────────────────────────────────
  if (scroll) {
    scroll.addEventListener('click', function (e) {
      e.preventDefault();
      var nextSection = hero.nextElementSibling;
      if (nextSection) {
        nextSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }
}());
