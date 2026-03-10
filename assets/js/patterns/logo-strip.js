/* Pattern: logo-strip
   Handles: clones the track once so the marquee loops seamlessly.
            Sets --logo-strip-duration proportional to item count.
   ------------------------------------------------------------------ */
(function () {
  'use strict';

  document.querySelectorAll('.logo-strip__track').forEach(function (track) {
    // Clone all items and append — CSS animation moves by –50% so the
    // second copy slots in exactly when the first exits.
    var items = track.querySelectorAll('.logo-strip__item');
    if (!items.length) return;

    items.forEach(function (item) {
      track.appendChild(item.cloneNode(true));
    });

    // Scale duration by number of logos so speed stays constant.
    // Base: 30s for 6 logos — ~5s per logo.
    var duration = Math.max(10, items.length * 5);
    track.style.setProperty('--logo-strip-duration', duration + 's');
  });
}());
