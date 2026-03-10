/* Pattern: product-image
   Handles: thumbnail switching, lightbox open/close, keyboard + click dismiss.
   ------------------------------------------------------------------ */
(function () {
  'use strict';

  document.querySelectorAll('.product-image').forEach(function (block) {
    var mainWrap  = block.querySelector('.product-image__main');
    var mainImg   = mainWrap && mainWrap.querySelector('img');
    var thumbs    = block.querySelectorAll('.product-image__thumb');
    var lightbox  = block.querySelector('.product-image__lightbox');
    var lbImg     = lightbox && lightbox.querySelector('img');
    var lbClose   = lightbox && lightbox.querySelector('.product-image__lightbox-close');

    // ── Thumbnail switcher ─────────────────────────────────────────────────────
    thumbs.forEach(function (thumb) {
      thumb.addEventListener('click', function () {
        var src = thumb.querySelector('img') && thumb.querySelector('img').src;
        if (src && mainImg) {
          mainImg.src = src;
          if (lbImg) lbImg.src = src;
        }
        thumbs.forEach(function (t) { t.classList.remove('is-active'); });
        thumb.classList.add('is-active');
      });
    });

    // Mark first thumb active on load
    if (thumbs.length) thumbs[0].classList.add('is-active');

    // ── Lightbox open ──────────────────────────────────────────────────────────
    if (mainWrap && lightbox) {
      mainWrap.addEventListener('click', function () {
        if (lbImg && mainImg) lbImg.src = mainImg.src;
        lightbox.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      });
    }

    // ── Lightbox close ─────────────────────────────────────────────────────────
    function closeLightbox() {
      if (!lightbox) return;
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
    }

    if (lbClose)  lbClose.addEventListener('click', closeLightbox);
    if (lightbox) {
      lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) closeLightbox();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeLightbox();
    });
  });
}());
