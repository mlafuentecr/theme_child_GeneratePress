/* Pattern: video-and-text
   Handles: play-button overlay for native <video> and iframe (YouTube/Vimeo).
   ------------------------------------------------------------------ */
(function () {
  'use strict';

  document.querySelectorAll('.video-and-text__media').forEach(function (media) {
    var playBtn = media.querySelector('.video-and-text__play');
    var video   = media.querySelector('video');
    var iframe  = media.querySelector('.video-and-text__embed');

    if (!playBtn) return;

    playBtn.addEventListener('click', function () {
      // Native video
      if (video) {
        video.play();
        playBtn.classList.add('is-hidden');
        video.addEventListener('pause', function () {
          playBtn.classList.remove('is-hidden');
        }, { once: false });
        return;
      }

      // iframe (YouTube / Vimeo autoplay via src param swap)
      if (iframe) {
        var src = iframe.src || iframe.dataset.src || '';
        if (src) {
          // Append or update autoplay param
          var separator = src.includes('?') ? '&' : '?';
          iframe.src = src.replace(/[?&]autoplay=\d/, '') + separator + 'autoplay=1';
          playBtn.classList.add('is-hidden');
        }
      }
    });
  });
}());
