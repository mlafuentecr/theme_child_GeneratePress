(function () {

  /* ==========================================
     SLIDER
  ========================================== */
  function initUseCaseSlider(article) {

    const gallery = article.querySelector('.wp-block-gallery');
    if (!gallery) return;

    const slides = Array.from(gallery.children);
    if (slides.length < 2) return;

    let index = 0;

    const wrapper = document.createElement('div');
    wrapper.className = 'use-case-slider';

    gallery.parentNode.insertBefore(wrapper, gallery);
    wrapper.appendChild(gallery);

    const prev = document.createElement('button');
    prev.className = 'use-case-arrow prev';
    prev.setAttribute('aria-label', 'Previous slide');
    prev.innerHTML = '‹';

    const next = document.createElement('button');
    next.className = 'use-case-arrow next';
    next.setAttribute('aria-label', 'Next slide');
    next.innerHTML = '›';

    wrapper.appendChild(prev);
    wrapper.appendChild(next);

    const dotsWrap = document.createElement('div');
    dotsWrap.className = 'use-case-dots';

    const dots = slides.map((_, i) => {
      const dot = document.createElement('button');
      dot.className = 'use-case-dot';
      dot.type = 'button';
      dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
      dot.addEventListener('click', () => goTo(i));
      dotsWrap.appendChild(dot);
      return dot;
    });

    wrapper.parentElement.insertBefore(dotsWrap, wrapper.nextSibling);

    function goTo(i) {
      index = Math.max(0, Math.min(slides.length - 1, i));
      slides[index].scrollIntoView({
        behavior: 'smooth',
        inline: 'center'
      });
      update();
    }

    function update() {
      dots.forEach((d, i) =>
        d.classList.toggle('active', i === index)
      );
    }

    prev.addEventListener('click', () => goTo(index - 1));
    next.addEventListener('click', () => goTo(index + 1));

    const observer = new IntersectionObserver(
      entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            index = slides.indexOf(entry.target);
            update();
          }
        });
      },
      { root: gallery, threshold: 0.6 }
    );

    slides.forEach(slide => observer.observe(slide));
    update();
  }

  let isLoading = false;

  /* ==========================================
     OPEN MODAL
  ========================================== */
  document.addEventListener('click', function (e) {

    const trigger = e.target.closest('.js-open-use-case');
    console.log(trigger, 'xxx');
    if (!trigger) return;

    e.preventDefault();
    if (isLoading) return;

   
    const postClass = Array.from(trigger.classList)
      .find(c => c.startsWith('post-'));

    if (!postClass) {
      console.warn('No post-* class found');
      return;
    }

    const postId = postClass.replace('post-', '');
    if (!postId) return;


    const modal = document.getElementById('use-case-modal');
    const body  = document.getElementById('use-case-modal-body');

    if (!modal || !body) {
      console.warn('Modal elements not found');
      return;
    }

    isLoading = true;

    modal.classList.remove('hidden');
    document.body.classList.add('modal-open');
    body.innerHTML = '<p>Loading…</p>';

    fetch(UseCasesAjax.ajaxurl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'load_use_case_modal',
        post_id: postId
      })
    })
    .then(r => r.text())
    .then(html => {

      body.innerHTML = html || '<p>No content found.</p>';

      const article = body.querySelector('.use-case-modal-article');
      if (article) initUseCaseSlider(article);

      isLoading = false;
    })
    .catch(() => {
      body.innerHTML = '<p>Error loading content.</p>';
      isLoading = false;
    });

  });

  /* ==========================================
     CLOSE MODAL
  ========================================== */
  function closeModal() {
    const modal = document.getElementById('use-case-modal');
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.classList.remove('modal-open');

    const body = document.getElementById('use-case-modal-body');
    if (body) body.innerHTML = '';

    isLoading = false;
  }

  document.addEventListener('click', e => {
    if (
      e.target.classList.contains('use-case-modal-overlay') ||
      e.target.classList.contains('use-case-modal-close')
    ) {
      closeModal();
    }
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      closeModal();
    }
  });

})();
