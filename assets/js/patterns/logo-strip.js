document.addEventListener('DOMContentLoaded', () => {
  const track = document.querySelector('.logo-strip-track');
  if (!track) return;

  const logos = [...track.children];

  logos.forEach(logo => {
    const clone = logo.cloneNode(true);
    clone.setAttribute('aria-hidden', 'true'); 
    track.appendChild(clone);
  });
});
