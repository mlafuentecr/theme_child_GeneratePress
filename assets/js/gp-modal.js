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
    // Open
    var trigger = e.target.closest('[data-gp-modal-open]');
    if (trigger) {
      e.preventDefault();
      var id    = trigger.dataset.gpModalOpen;
      var modal = document.getElementById(id);
      openModal(modal);
      return;
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
