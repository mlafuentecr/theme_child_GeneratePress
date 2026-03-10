document.addEventListener('DOMContentLoaded', () => {
  const wrapper = document.querySelector('.search-results');
  if (!wrapper) return;

  const container = wrapper.querySelector('.search-results-container');
  const tabs = document.querySelectorAll('.tab');
  const loadMoreBtn = document.querySelector('.load-more-button');

  let currentPage = 1;
  let currentType = 'all';

  const search = wrapper.dataset.search;
  const postsPerPage = wrapper.dataset.postsPerPage;

  function fetchResults(reset = false) {
    if (reset) {
      currentPage = 1;
      container.innerHTML = '';
    }

    const formData = new FormData();
    formData.append('action', 'load_more_search_results');
    formData.append('nonce', search_ajax.nonce);
    formData.append('search', search);
    formData.append('post_type', currentType);
    formData.append('page', currentPage);
    formData.append('posts_per_page', postsPerPage);

    fetch(search_ajax.ajax_url, {
      method: 'POST',
      body: formData,
    })
      .then(res => res.json())
      .then(data => {
        if (!data.success) return;

        // 🔥 SOLO UNA INSERCIÓN
        if (reset) {
          container.innerHTML = data.data.html;
        } else {
          container.insertAdjacentHTML('beforeend', data.data.html);
        }

        if (!data.data.has_more && loadMoreBtn) {
          loadMoreBtn.style.display = 'none';
        } else if (loadMoreBtn) {
          loadMoreBtn.style.display = 'inline-block';
        }
      });
  }

  tabs.forEach(tab => {
    tab.addEventListener('click', e => {
      e.preventDefault();

      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');

      currentType = tab.dataset.postType;

      fetchResults(true);
    });
  });

  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', () => {
      currentPage++;
      fetchResults();
    });
  }
});
