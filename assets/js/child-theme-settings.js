/* global gpChildSettings, wp */
(function () {
    'use strict';

    var frame;

    var btnSet    = document.getElementById('gp-dfi-set');
    var btnRemove = document.getElementById('gp-dfi-remove');
    var input     = document.getElementById('gp_dfi_id');
    var preview   = document.getElementById('gp-dfi-preview');

    if (!btnSet || !btnRemove || !input || !preview) {
        return;
    }

    // Open the WordPress media library.
    btnSet.addEventListener('click', function (e) {
        e.preventDefault();

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title:    gpChildSettings.mediaTitle,
            button:   { text: gpChildSettings.mediaButton },
            multiple: false,
            library:  { type: 'image' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            input.value   = attachment.id;

            // Update preview.
            preview.innerHTML = '<img src="' + attachment.url + '" width="128" height="128" style="object-fit:cover;">';

            // Enable the remove button.
            btnRemove.classList.remove('button-disabled');
        });

        frame.open();
    });

    // Clear the selection.
    btnRemove.addEventListener('click', function (e) {
        e.preventDefault();
        if (btnRemove.classList.contains('button-disabled')) {
            return;
        }
        input.value       = '';
        preview.innerHTML = '';
        btnRemove.classList.add('button-disabled');
    });

}());
