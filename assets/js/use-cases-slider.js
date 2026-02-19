function initUseCaseSlider(article) {

  const gallery = article.querySelector('.wp-block-gallery');
  if (!gallery) return;

  const slides = Array.from(gallery.children);
  if (slides.length < 2) return;

  gallery.setAttribute('role', 'region');
  gallery.setAttribute('aria-label', 'Image carousel');
  gallery.tabIndex = 0;

  const dotsWrap = document.createElement('div');
  dotsWrap.className = 'use-case-slider-dots';

  let current = 0;

  function goTo(index) {
    index = Math.max(0, Math.min(slides.length - 1, index));
    slides[index].scrollIntoView({ behavior: 'smooth', inline: 'center' });
    setActive(index);
  }

  function setActive(index) {
    current = index;
    dotsWrap.querySelectorAll('button').forEach((b, i) => {
      b.setAttribute('aria-current', i === index);
    });
  }

  slides.forEach((_, i) => {
    const btn = document.createElement('button');
    btn.className = 'use-case-slider-dot';
    btn.type = 'button';
    btn.setAttribute('aria-label', `Go to slide ${i + 1}`);
    btn.addEventListener('click', () => goTo(i));
    dotsWrap.appendChild(btn);
  });

  article.appendChild(dotsWrap);

  const observer = new IntersectionObserver(
    entries => {
      entries.forEach(e => {
        if (e.isIntersecting) {
          setActive(slides.indexOf(e.target));
        }
      });
    },
    { root: gallery, threshold: 0.6 }
  );

  slides.forEach(s => observer.observe(s));

  setActive(0);
}
