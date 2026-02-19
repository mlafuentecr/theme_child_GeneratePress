document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.top-search-toggle');
  const topBar = document.querySelector('.signifi-top-bar');
  const searchInput = document.querySelector('.top-bar-search input[type="search"]');

  if (!toggle || !topBar) return;

  toggle.addEventListener('click', () => {
    topBar.classList.toggle('search-open');

    if (topBar.classList.contains('search-open') && searchInput) {
      setTimeout(() => searchInput.focus(), 150);
    }
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      topBar.classList.remove('search-open');
    }
  });
});
