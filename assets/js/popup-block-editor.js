/**
 * GP Popup — Custom Gutenberg Block (overlay only, no built-in trigger).
 * Trigger the popup by linking any button/link to #popup-id.
 * No JSX / no build process. Uses window.wp.element.createElement.
 */
(function (blocks, blockEditor, components, element, i18n) {
    'use strict';

    var el            = element.createElement;
    var Fragment      = element.Fragment;
    var __            = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var InnerBlocks       = blockEditor.InnerBlocks;
    var useBlockProps     = blockEditor.useBlockProps;
    var PanelBody    = components.PanelBody;
    var TextControl  = components.TextControl;
    var SelectControl = components.SelectControl;
    var ToggleControl = components.ToggleControl;

    // Brand slug injected from PHP via wp_localize_script (popup-block.php)
    var gpData    = window.gpPopupBlockData || {};
    var brandSlug = gpData.brandSlug || 'blueflamingo';

    var INNER_TEMPLATE = [
        ['core/heading',   { level: 3, placeholder: 'Popup Title' }],
        ['core/paragraph', { placeholder: 'Your content here...' }],
    ];

    // SVG ×-icon — identical to inc/modal.php line 78
    function svgClose() {
        return el('svg', {
            'aria-hidden':  'true',
            focusable:      'false',
            width: '18', height: '18',
            viewBox: '0 0 24 24',
            fill: 'none', stroke: 'currentColor',
            strokeWidth: '2.5', strokeLinecap: 'round', strokeLinejoin: 'round',
        },
            el('line', { x1: '18', y1: '6',  x2: '6',  y2: '18' }),
            el('line', { x1: '6',  y1: '6',  x2: '18', y2: '18' })
        );
    }

    blocks.registerBlockType('generatepress-child/popup', {
        title:       __('Popup', 'generatepress-child'),
        description: __('Accessible popup overlay. Trigger it by linking any button to #popup-id.', 'generatepress-child'),
        icon:        'external',
        category:    brandSlug,
        keywords:    [__('popup'), __('modal'), __('dialog'), __('overlay')],

        attributes: {
            popupId:      { type: 'string',  default: 'popup-1' },
            popupSize:    { type: 'string',  default: 'md'      },
            closeOutside: { type: 'boolean', default: true      },
        },

        // ── Editor view ───────────────────────────────────────────────────────
        edit: function (props) {
            var a   = props.attributes;
            var set = props.setAttributes;
            var blockProps = useBlockProps({ className: 'gp-popup-block-editor' });

            return el(Fragment, null,

                // ── Sidebar controls ─────────────────────────────────────────
                el(InspectorControls, null,
                    el(PanelBody, {
                        title:       __('Popup Settings', 'generatepress-child'),
                        initialOpen: true,
                    },
                        el(TextControl, {
                            label: __('Popup ID', 'generatepress-child'),
                            help:  __('Link any button or element to #' + a.popupId + ' to open this popup.', 'generatepress-child'),
                            value: a.popupId,
                            onChange: function (v) {
                                set({ popupId: v.replace(/\s+/g, '-').toLowerCase() });
                            },
                        }),
                        el(SelectControl, {
                            label: __('Size', 'generatepress-child'),
                            value: a.popupSize,
                            options: [
                                { label: __('Small — 400px',    'generatepress-child'), value: 'sm'   },
                                { label: __('Medium — 580px',   'generatepress-child'), value: 'md'   },
                                { label: __('Large — 780px',    'generatepress-child'), value: 'lg'   },
                                { label: __('X-Large — 1020px', 'generatepress-child'), value: 'xl'   },
                                { label: __('Full Screen',      'generatepress-child'), value: 'full' },
                            ],
                            onChange: function (v) { set({ popupSize: v }); },
                        }),
                        el(ToggleControl, {
                            label:    __('Close on overlay click', 'generatepress-child'),
                            checked:  a.closeOutside,
                            onChange: function (v) { set({ closeOutside: v }); },
                        })
                    )
                ),

                // ── Canvas ───────────────────────────────────────────────────
                el('div', blockProps,
                    // Trigger hint
                    el('div', {
                        style: {
                            display:       'flex',
                            alignItems:    'center',
                            gap:           '8px',
                            padding:       '8px 12px',
                            marginBottom:  '8px',
                            background:    '#f0f6fc',
                            border:        '1px solid #c8e1f5',
                            borderRadius:  '4px',
                            fontSize:      '12px',
                            color:         '#0969da',
                        },
                    },
                        el('span', { style: { fontSize: '14px' } }, '💡'),
                        el('span', null,
                            'Trigger: link any button to ',
                            el('code', {
                                style: {
                                    background:   '#dbeafe',
                                    padding:      '1px 5px',
                                    borderRadius: '3px',
                                    fontFamily:   'monospace',
                                },
                            }, '#' + a.popupId)
                        )
                    ),
                    // Popup content editing area
                    el('div', {
                        style: {
                            border:       '2px dashed #007cba',
                            borderRadius: '4px',
                            padding:      '12px 16px',
                        },
                    },
                        el('p', {
                            style: {
                                margin:        '0 0 10px',
                                fontSize:      '11px',
                                fontWeight:    '600',
                                textTransform: 'uppercase',
                                letterSpacing: '0.5px',
                                color:         '#007cba',
                            },
                        }, 'Popup Content — ' + a.popupId + ' [' + a.popupSize + ']'),
                        el(InnerBlocks, {
                            template:     INNER_TEMPLATE,
                            templateLock: false,
                        })
                    )
                )
            );
        },

        // ── Front-end output (overlay only) ───────────────────────────────────
        save: function (props) {
            var a = props.attributes;

            return el('div', { className: 'gp-popup-block' },
                el('div', {
                    id:                   a.popupId,
                    className:            'gp-modal gp-modal--' + a.popupSize,
                    role:                 'dialog',
                    'aria-modal':         'true',
                    'aria-hidden':        'true',
                    'data-close-outside': String(a.closeOutside),
                    tabIndex:             '-1',
                },
                    el('div', {
                        className:             'gp-modal__overlay',
                        'data-gp-modal-close': '',
                    }),
                    el('div', { className: 'gp-modal__container' },
                        el('button', {
                            type:                  'button',
                            className:             'gp-modal__close',
                            'data-gp-modal-close': '',
                            'aria-label':          'Close',
                        }, svgClose()),
                        el('div', { className: 'gp-modal__content' },
                            el(InnerBlocks.Content, null)
                        )
                    )
                )
            );
        },
    });

}(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n));
