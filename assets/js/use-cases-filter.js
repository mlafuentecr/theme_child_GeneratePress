document.addEventListener('DOMContentLoaded', function () {
  const resultsContainer = document.getElementById('use-cases-results');
  const loadMoreBtn = document.getElementById('load-more-use-cases');

  const industrySelect = document.getElementById('filter-industry');
  const solutionSelect = document.getElementById('filter-solution');
  const productSelect  = document.getElementById('filter-product');
  const searchInput    = document.getElementById('filter-search');
  const resultsWrap    = document.getElementById('use-cases-results');
  const clearBtn       = document.getElementById('clear-use-cases-filters');
  const ctaUseCases    = document.querySelector('.use-cases-cta');

  let page = 1;

  if (!resultsWrap) {
    console.warn('Use Cases filter not found on this page');
    return;
  }

  /* ============================================================
   * Helper – Extract slug from URL value (legacy support)
   * ============================================================ */
  function extractSlug(value) {
    if (!value) return '';
    try {
      const url = new URL(value);
      return url.pathname.split('/').filter(Boolean).pop();
    } catch {
      return value;
    }
  }

  /* ============================================================
   * Helper – Check if any filter is active
   * ============================================================ */
  function hasActiveFilters() {
    return (
      extractSlug(industrySelect.value) ||
      extractSlug(solutionSelect.value) ||
      extractSlug(productSelect.value) ||
      searchInput.value.trim()
    );
  }

  /* ============================================================
   * Helper – Toggle Clear Filters button
   * ============================================================ */
  function toggleClearButton() {
    if (!clearBtn) return;

    if (hasActiveFilters()) {
      clearBtn.classList.remove('d-none');
    } else {
      clearBtn.classList.add('d-none');
    }
  }

  /* ============================================================
   * URL – Sync filters to query string (shareable links)
   * ============================================================ */
  function updateURL() {
    const params = new URLSearchParams();

    const industry = extractSlug(industrySelect.value);
    const solution = extractSlug(solutionSelect.value);
    const product  = extractSlug(productSelect.value);
    const search   = searchInput.value.trim();

    if (industry) params.set('uc_industry', industry);
    if (solution) params.set('uc_solution', solution);
    if (product)  params.set('uc_product', product);
    if (search)   params.set('uc_search', search);

    const newURL = params.toString()
      ? window.location.pathname + '?' + params.toString()
      : window.location.pathname;

    history.pushState({ industry, solution, product, search }, '', newURL);
  }

  /* ============================================================
   * AJAX – Fetch Use Cases
   * ============================================================ */
  function fetchUseCases(reset = false, pushURL = true) {
    if (reset) {
      page = 1;
      resultsContainer.innerHTML = '';
      loadMoreBtn.classList.add('d-none');
    }

    /* Sync URL when filters change (not on load-more) */
    if (pushURL) {
      updateURL();
    }

    /* Toggle CTA visibility */
    if (ctaUseCases) {
      if (hasActiveFilters()) {
        ctaUseCases.classList.add('d-none');
      } else {
        ctaUseCases.classList.remove('d-none');
      }
    }

    toggleClearButton();

    const formData = new FormData();
    formData.append('action', 'filter_use_cases');
    formData.append('industry', industrySelect ? extractSlug(industrySelect.value) : '');
    formData.append('solution', solutionSelect ? extractSlug(solutionSelect.value) : '');
    formData.append('product',  productSelect  ? extractSlug(productSelect.value)  : '');
    formData.append('search',   searchInput    ? searchInput.value                 : '');
    formData.append('page', page);

    fetch(UseCasesAjax.ajaxurl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(res => res.text())
      .then(html => {
        if (html.trim()) {
          // Remove old has-more markers
          resultsContainer
            .querySelectorAll('.has-more')
            .forEach(el => el.remove());

          resultsContainer.insertAdjacentHTML('beforeend', html);

          if (resultsContainer.querySelector('.has-more')) {
            loadMoreBtn.classList.remove('d-none');
          } else {
            loadMoreBtn.classList.add('d-none');
          }
        } else {
          if (reset) {
            resultsContainer.innerHTML = '<div class="use-cases-empty">No results found.</div>';
          }
          loadMoreBtn.classList.add('d-none');
        }
      })
      .catch(err => {
        console.error('AJAX error', err);
      });
  }

  /* ============================================================
   * Load More button
   * ============================================================ */
  loadMoreBtn.addEventListener('click', function (e) {
    e.preventDefault();
    page++;
    fetchUseCases(false, false);
  });

  /* ============================================================
   * Filters change
   * ============================================================ */
  [industrySelect, solutionSelect, productSelect].forEach(select => {
    if (!select) return;
    select.addEventListener('change', () => fetchUseCases(true));
  });

  /* ============================================================
   * Search input
   * ============================================================ */
  if (searchInput) {
    searchInput.addEventListener('input', () => fetchUseCases(true));
  }

  /* ============================================================
   * Clear filters button
   * ============================================================ */
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      industrySelect.value = '';
      solutionSelect.value = '';
      productSelect.value  = '';
      searchInput.value    = '';

      fetchUseCases(true);
    });
  }

  /* ============================================================
   * Browser back/forward navigation
   * ============================================================ */
  window.addEventListener('popstate', function (e) {
    const state = e.state || {};

    if (industrySelect) industrySelect.value = state.industry || '';
    if (solutionSelect) solutionSelect.value = state.solution || '';
    if (productSelect)  productSelect.value  = state.product  || '';
    if (searchInput)    searchInput.value    = state.search   || '';

    fetchUseCases(true, false);
  });

  /* ============================================================
   * Initial load – uses values already set by PHP from URL params
   * ============================================================ */
  fetchUseCases(true, false);
});
