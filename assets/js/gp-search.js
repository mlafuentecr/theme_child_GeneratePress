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
    var postTypes  = wrap.dataset.postTypes  || 'post,page';
    var limit      = wrap.dataset.limit       || 5;
    var activeIdx  = -1;
    var items      = [];

    if (!input || !results) return;

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
        input.focus();
      }
    });

    // Close on outside click
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) setExpanded(false);
    });
  }

  document.querySelectorAll('.gp-search-wrap').forEach(initSearch);
})();
