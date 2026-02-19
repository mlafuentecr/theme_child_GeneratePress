<?php

add_action( 'init', function () {

    register_block_style(
        'core/button',
        [
            'name'     => 'signifi-primary',
            'label'    => 'Signifi Primary',
            'category' => 'signifi',
        ]
    );

    register_block_style(
        'core/button',
        [
            'name'     => 'signifi-secondary',
            'label'    => 'Signifi Secondary',
            'category' => 'signifi',
        ]
    );

    register_block_style(
        'core/button',
        [
            'name'     => 'signifi-outline',
            'label'    => 'Signifi Outline',
            'category' => 'signifi',
        ]
    );

});
