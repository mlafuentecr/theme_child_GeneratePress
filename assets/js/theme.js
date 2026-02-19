(function () {
  const toggles = document.querySelectorAll('[data-toggle-target]');

  toggles.forEach((button) => {
    button.addEventListener('click', () => {
      const selector = button.getAttribute('data-toggle-target');
      const target = selector ? document.querySelector(selector) : null;

      if (!target) return;

      target.hidden = !target.hidden;
    });
  });
})();
