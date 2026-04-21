/**
 * GP Search — debounced AJAX live search.
 * Depends on `gpSearch` localisation object (ajaxUrl, nonce, noResults, searching).
 */
(function () {
  'use strict';

  var DEBOUNCE_MS = 280;
  var MIN_CHARS   = 2;

  function debounce(fn, ms) {
    var timer;
    return function () {
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () { fn.apply(this, args); }, ms);
    };
  }

  function initSearch(wrap) {
    var input      = wrap.querySelector('.gp-search-input');
    var results    = wrap.querySelector('.gp-search-results');
    var toggle     = wrap.querySelector('.gp-search-toggle');
    var form       = wrap.tagName === 'FORM' ? wrap : wrap.closest('form');
    var postTypes  = wrap.dataset.postTypes  || 'post,page';
    var limit      = wrap.dataset.limit       || 5;
    var mode       = wrap.dataset.mode        || 'live_ajax';
    var variant    = wrap.dataset.variant     || 'full';
    var activeIdx  = -1;
    var items      = [];

    if (!input) return;

    function setOpen(open) {
      if (variant !== 'icon') return;
      wrap.classList.toggle('gp-search-wrap--open', open);
      input.hidden = !open;
      if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        window.requestAnimationFrame(function () {
          input.focus();
        });
      } else {
        input.hidden = true;
        input.value = '';
        if (results) {
          results.innerHTML = '';
          results.setAttribute('hidden', '');
        }
      }
    }

    if (toggle) {
      toggle.addEventListener('click', function () {
        setOpen(!wrap.classList.contains('gp-search-wrap--open'));
      });
    }

    function submitResultsSearch() {
      if (!form) return;
      if (!input.value.trim()) return;
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }

    if (mode === 'results_page' || !results) {
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          e.stopPropagation();
          submitResultsSearch();
        }
      });

      document.addEventListener('click', function (e) {
        if (variant === 'icon' && !wrap.contains(e.target)) {
          setOpen(false);
        }
      });
      return;
    }

    function setExpanded(open) {
      input.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (open) {
        results.removeAttribute('hidden');
      } else {
        results.setAttribute('hidden', '');
        activeIdx = -1;
      }
    }

    function setActive(idx) {
      items = results.querySelectorAll('.gp-search-result');
      items.forEach(function (el, i) {
        el.setAttribute('aria-selected', i === idx ? 'true' : 'false');
      });
      activeIdx = idx;
    }

    function showStatus(msg) {
      results.innerHTML = '<div class="gp-search-status">' + msg + '</div>';
      setExpanded(true);
    }

    function render(data) {
      if (!data.length) {
        showStatus(gpSearch.noResults);
        return;
      }
      results.innerHTML = data.map(function (item) {
        return '<a href="' + item.url + '" class="gp-search-result" role="option" aria-selected="false">' +
               '<span class="gp-search-result__title">' + item.title + '</span>' +
               '<span class="gp-search-result__type">' + item.type + '</span>' +
               '</a>';
      }).join('');
      setExpanded(true);
    }

    var search = debounce(function (query) {
      if (query.length < MIN_CHARS) { setExpanded(false); return; }

      showStatus(gpSearch.searching);

      var body = new URLSearchParams({
        action:     'gp_search',
        nonce:      gpSearch.nonce,
        query:      query,
        post_types: postTypes,
        limit:      limit
      });

      fetch(gpSearch.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (res) { if (res.success) render(res.data); })
        .catch(function () { setExpanded(false); });
    }, DEBOUNCE_MS);

    input.addEventListener('input', function () { search(input.value.trim()); });

    // Keyboard navigation
    input.addEventListener('keydown', function (e) {
      items = results.querySelectorAll('.gp-search-result');
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIdx + 1, items.length - 1));
        if (items[activeIdx]) items[activeIdx].focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIdx - 1, 0));
        if (items[activeIdx]) items[activeIdx].focus();
      } else if (e.key === 'Escape') {
        setExpanded(false);
        if (variant === 'icon') {
          setOpen(false);
          if (toggle) toggle.focus();
          return;
        }
        input.focus();
      }
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) {
        setExpanded(false);
        if (variant === 'icon') {
          setOpen(false);
        }
      }
    });
  }

  document.querySelectorAll('.gp-search-wrap').forEach(initSearch);
})();
