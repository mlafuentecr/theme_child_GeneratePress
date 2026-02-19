document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.video-and-text .video').forEach(video => {
    video.addEventListener('click', () => {
      video.classList.add('is-playing');

      const iframe = video.querySelector('iframe');
      if (iframe && iframe.src.includes('youtube')) {
        if (!iframe.src.includes('autoplay=1')) {
          iframe.src += (iframe.src.includes('?') ? '&' : '?') + 'autoplay=1';
        }
      }
    }, { once: true });
  });
});
