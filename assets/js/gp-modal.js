/**
 * GP Modal — lightweight accessible modal system.
 * Supports: keyboard nav, focus trap, Escape to close, click-outside close.
 */
(function () {
  'use strict';

  var FOCUSABLE = [
    'a[href]', 'button:not([disabled])', 'input:not([disabled])',
    'select:not([disabled])', 'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])'
  ].join(',');

  var openModals = [];
  var galleryModal = null;

  // ── Open ────────────────────────────────────────────────────────────────────

  function openModal(modal) {
    if (!modal || modal.classList.contains('is-open')) return;

    modal.setAttribute('aria-hidden', 'false');
    modal.classList.add('is-open');
    document.body.classList.add('gp-modal-open');

    // Store previously focused element
    modal._previousFocus = document.activeElement;

    // Focus first focusable element inside
    var focusable = modal.querySelectorAll(FOCUSABLE);
    if (focusable.length) {
      setTimeout(function () { focusable[0].focus(); }, 50);
    }

    openModals.push(modal);
  }

  // ── Close ───────────────────────────────────────────────────────────────────

  function closeModal(modal) {
    if (!modal || !modal.classList.contains('is-open')) return;

    modal.setAttribute('aria-hidden', 'true');
    modal.classList.remove('is-open');

    openModals = openModals.filter(function (m) { return m !== modal; });
    if (!openModals.length) document.body.classList.remove('gp-modal-open');

    // Restore focus
    if (modal._previousFocus && typeof modal._previousFocus.focus === 'function') {
      modal._previousFocus.focus();
    }
  }

  function isImageUrl(url) {
    return /\.(avif|bmp|gif|jpe?g|png|svg|webp)(\?.*)?$/i.test(url || '');
  }

  function removeImageSizeSuffix(url) {
    if (!url) return '';
    return url.replace(/-\d+x\d+(?=\.[a-z0-9]{3,4}(?:\?.*)?$)/i, '');
  }

  function getLargestSrcFromSrcset(srcset) {
    if (!srcset) return '';

    var bestUrl = '';
    var bestWidth = 0;

    srcset.split(',').forEach(function (candidate) {
      var part = candidate.trim();
      if (!part) return;

      var bits = part.split(/\s+/);
      var url = bits[0] || '';
      var size = bits[1] || '';
      var width = 0;

      if (/^\d+w$/.test(size)) {
        width = parseInt(size, 10) || 0;
      }

      if (width >= bestWidth) {
        bestWidth = width;
        bestUrl = url;
      }
    });

    return bestUrl;
  }

  function getGalleryImageUrl(img) {
    if (!img) return '';

    var dataFull = img.getAttribute('data-full-url');
    if (dataFull) return dataFull;

    var link = img.closest('a[href]');
    if (link) {
      var href = link.getAttribute('href');
      if (isImageUrl(href)) {
        return href;
      }
    }

    var srcsetUrl = getLargestSrcFromSrcset(img.getAttribute('srcset'));
    if (srcsetUrl) {
      return srcsetUrl;
    }

    var src = img.currentSrc || img.getAttribute('src') || '';
    return removeImageSizeSuffix(src);
  }

  function ensureGalleryModal() {
    if (galleryModal) return galleryModal;

    var wrapper = document.createElement('div');
    wrapper.id = 'gp-gallery-modal';
    wrapper.className = 'gp-modal gp-gallery-modal';
    wrapper.setAttribute('role', 'dialog');
    wrapper.setAttribute('aria-modal', 'true');
    wrapper.setAttribute('aria-hidden', 'true');
    wrapper.setAttribute('data-close-outside', 'true');
    wrapper.setAttribute('tabindex', '-1');

    wrapper.innerHTML = [
      '<div class="gp-modal__overlay" data-gp-modal-close></div>',
      '<div class="gp-modal__container">',
      '<div class="gp-modal__content">',
      '<button class="gp-modal__close" data-gp-modal-close aria-label="Close">',
      '<svg aria-hidden="true" focusable="false" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
      '</button>',
      '<img class="gp-gallery-modal__image" src="" alt="">',
      '</div>',
      '</div>'
    ].join('');

    document.body.appendChild(wrapper);
    galleryModal = wrapper;
    return galleryModal;
  }

  // ── Focus trap ──────────────────────────────────────────────────────────────

  document.addEventListener('keydown', function (e) {
    if (!openModals.length) return;
    var modal = openModals[openModals.length - 1];

    if (e.key === 'Escape') {
      closeModal(modal);
      return;
    }

    if (e.key !== 'Tab') return;

    var focusable = Array.prototype.slice.call(modal.querySelectorAll(FOCUSABLE));
    if (!focusable.length) { e.preventDefault(); return; }

    var first = focusable[0];
    var last  = focusable[focusable.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });

  // ── Delegation: open triggers ───────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var galleryImg = e.target.closest('.wp-block-gallery img, .gallery img, .blocks-gallery-grid img');
    if (
      galleryImg &&
      !galleryImg.closest('.wp-lightbox-container') &&
      !galleryImg.closest('.wp-lightbox-overlay')
    ) {
      var galleryRoot = galleryImg.closest('.wp-block-gallery, .gallery, .blocks-gallery-grid');
      if (galleryRoot) {
        var imageUrl = getGalleryImageUrl(galleryImg);
        if (imageUrl) {
          e.preventDefault();
          var modal = ensureGalleryModal();
          var modalImage = modal.querySelector('.gp-gallery-modal__image');
          if (modalImage) {
            modalImage.src = imageUrl;
            modalImage.alt = galleryImg.getAttribute('alt') || '';
          }
          openModal(modal);
          return;
        }
      }
    }

    // Primary trigger: data-gp-modal-open="popup-id"
    var trigger = e.target.closest('[data-gp-modal-open]');
    if (trigger) {
      e.preventDefault();
      var id    = trigger.dataset.gpModalOpen;
      var modal = document.getElementById(id);
      openModal(modal);
      return;
    }

    // Fallback trigger: <a href="#popup-id"> pointing to a .gp-modal element
    var hashLink = e.target.closest('a[href^="#"]');
    if (hashLink) {
      var hash   = hashLink.getAttribute('href').slice(1);
      var target = hash ? document.getElementById(hash) : null;
      if (target && target.classList.contains('gp-modal')) {
        e.preventDefault();
        openModal(target);
        return;
      }
    }

    // Close button or overlay
    var closeBtn = e.target.closest('[data-gp-modal-close]');
    if (closeBtn) {
      var parentModal = closeBtn.closest('.gp-modal');
      if (parentModal) {
        // Overlay: only close if click-outside is enabled
        if (closeBtn.classList.contains('gp-modal__overlay')) {
          if (parentModal.dataset.closeOutside === 'false') return;
        }
        closeModal(parentModal);
      }
    }
  });
})();
