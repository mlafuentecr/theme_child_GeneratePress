/**
 * Video Hero — Custom Gutenberg Block
 *
 * Three-layer hero: fallback image → Vimeo background video → editable WP blocks.
 * No build process — uses window.wp globals only.
 *
 * Attributes (stored in block comment, PHP reads them for front-end render):
 *   vimeoId      – Vimeo numeric video ID
 *   fallbackUrl  – image URL shown on mobile / while video loads
 *   fallbackId   – media library attachment ID (for media picker state)
 *   fallbackAlt  – alt text for the fallback image
 *   minHeight    – minimum height of the hero in px
 */
(function (blocks, blockEditor, components, element, i18n) {
    'use strict';

    var el                = element.createElement;
    var Fragment          = element.Fragment;
    var __                = i18n.__;
    var useState          = element.useState;

    var InspectorControls = blockEditor.InspectorControls;
    var InnerBlocks       = blockEditor.InnerBlocks;
    var useBlockProps     = blockEditor.useBlockProps;
    var MediaUpload       = blockEditor.MediaUpload;
    var MediaUploadCheck  = blockEditor.MediaUploadCheck;

    var PanelBody         = components.PanelBody;
    var PanelRow          = components.PanelRow;
    var TextControl       = components.TextControl;
    var RangeControl      = components.RangeControl;
    var Button            = components.Button;
    var Notice            = components.Notice;
    var Spinner           = components.Spinner;
    var FocalPointPicker  = components.FocalPointPicker;

    // Brand slug injected via wp_localize_script in video-hero-block.php
    var gpData    = window.gpVideoHeroData || {};
    var brandSlug = gpData.brandSlug || 'blueflamingo';

    // Default inner blocks template
    var INNER_TEMPLATE = [
        ['core/heading', {
            level:       2,
            textAlign:   'center',
            placeholder: __('Hero Heading…', 'generatepress-child'),
        }],
        ['core/paragraph', {
            align:       'center',
            placeholder: __('Supporting text…', 'generatepress-child'),
        }],
    ];

    /* ── Helpers ────────────────────────────────────────────────────────────── */

    function badge(text, color) {
        return el('span', {
            style: {
                display:      'inline-flex',
                alignItems:   'center',
                gap:          '5px',
                background:   color || 'rgba(0,0,0,0.65)',
                color:        '#fff',
                fontSize:     '11px',
                fontWeight:   '600',
                padding:      '3px 9px',
                borderRadius: '20px',
                whiteSpace:   'nowrap',
            },
        }, text);
    }

    /* ── Block registration ─────────────────────────────────────────────────── */

    blocks.registerBlockType(brandSlug + '/video-hero', {

        title:       __('Video Hero', 'generatepress-child'),
        description: __('Hero with fallback image, Vimeo background video, and editable content blocks on top.', 'generatepress-child'),
        icon:        'format-video',
        category:    brandSlug,
        keywords:    ['hero', 'video', 'vimeo', 'background', 'banner'],

        attributes: {
            vimeoId:      { type: 'string',  default: ''   },
            vimeoParams:  { type: 'string',  default: 'autoplay=1&muted=1&loop=1&background=1' },
            videoFocalY:  { type: 'number',  default: 50   },
            fallbackUrl:  { type: 'string',  default: ''   },
            fallbackId:   { type: 'number',  default: 0    },
            fallbackAlt:  { type: 'string',  default: ''   },
            focalPoint:        { type: 'object',  default: { x: 0.5, y: 0.5 } },
            minHeight:         { type: 'number',  default: 560  },
            minHeightTablet:   { type: 'number',  default: 500  },
            minHeightMobile:   { type: 'number',  default: 420  },
        },

        /* ── Editor view ──────────────────────────────────────────────────── */
        edit: function (props) {
            var a   = props.attributes;
            var set = props.setAttributes;

            var blockProps = useBlockProps({
                style: {
                    position:   'relative',
                    overflow:   'hidden',
                    minHeight:  a.minHeight + 'px',
                    background: '#021C3D',
                    display:    'flex',
                    flexDirection: 'column',
                },
            });

            var hasFallback = !!a.fallbackUrl;
            var hasVideo    = !!a.vimeoId;
            var isReady     = hasFallback && hasVideo;

            return el(Fragment, null,

                /* ── Sidebar ─────────────────────────────────────────────── */
                el(InspectorControls, null,

                    el(PanelBody, {
                        title:       __('🎬 Video Layer', 'generatepress-child'),
                        initialOpen: true,
                    },
                        el(TextControl, {
                            label:    __('Vimeo Video ID', 'generatepress-child'),
                            help:     __('Only the numeric ID — e.g. 1172255452', 'generatepress-child'),
                            value:    a.vimeoId,
                            placeholder: '1172255452',
                            onChange: function (v) {
                                set({ vimeoId: v.replace(/\D/g, '') });
                            },
                        }),
                        hasVideo && el('div', {
                            style: {
                                marginTop:    '4px',
                                padding:      '8px 10px',
                                background:   '#f0fdf4',
                                border:       '1px solid #bbf7d0',
                                borderRadius: '4px',
                                fontSize:     '12px',
                                color:        '#166534',
                            },
                        },
                            '✓ ',
                            el('a', {
                                href:   'https://vimeo.com/' + a.vimeoId,
                                target: '_blank',
                                rel:    'noopener noreferrer',
                                style:  { color: '#166534' },
                            }, 'vimeo.com/' + a.vimeoId)
                        ),

                        el(TextControl, {
                            label:    __('URL Parameters', 'generatepress-child'),
                            help:     __('Query string appended to the Vimeo player URL.', 'generatepress-child'),
                            value:    a.vimeoParams,
                            onChange: function (v) { set({ vimeoParams: v }); },
                        }),

                        /* Live preview of the full player URL */
                        a.vimeoId && el('div', {
                            style: {
                                marginTop:    '4px',
                                padding:      '8px 10px',
                                background:   '#f8faff',
                                border:       '1px solid #c7d2fe',
                                borderRadius: '4px',
                                fontSize:     '11px',
                                color:        '#3730a3',
                                wordBreak:    'break-all',
                                fontFamily:   'monospace',
                            },
                        },
                            'player.vimeo.com/video/' + a.vimeoId + '?' + (a.vimeoParams || '')
                        ),

                        el(RangeControl, {
                            label:            __('Vertical Position', 'generatepress-child'),
                            help:             __('0 = top  ·  50 = center  ·  100 = bottom', 'generatepress-child'),
                            value:            a.videoFocalY,
                            min:              0,
                            max:              100,
                            step:             5,
                            onChange: function (v) { set({ videoFocalY: v }); },
                        })
                    ),

                    el(PanelBody, {
                        title:       __('🖼️ Fallback Image', 'generatepress-child'),
                        initialOpen: true,
                    },
                        el('p', {
                            style: { margin: '0 0 10px', fontSize: '12px', color: '#757575', lineHeight: '1.5' },
                        }, __('Shown on mobile and while the video loads.', 'generatepress-child')),

                        /* Focal Point Picker — shows image thumbnail + draggable dot */
                        hasFallback && el(FocalPointPicker, {
                            label:    __('Focal Point', 'generatepress-child'),
                            help:     __('Drag to choose which part of the image stays visible.', 'generatepress-child'),
                            url:      a.fallbackUrl,
                            value:    a.focalPoint,
                            onChange: function (fp) { set({ focalPoint: fp }); },
                        }),

                        /* Media picker button */
                        el(MediaUploadCheck, null,
                            el(MediaUpload, {
                                onSelect: function (media) {
                                    set({
                                        fallbackUrl: media.url,
                                        fallbackId:  media.id,
                                        fallbackAlt: media.alt || '',
                                    });
                                },
                                allowedTypes: ['image'],
                                value:  a.fallbackId,
                                render: function (ref) {
                                    return el(Button, {
                                        onClick:  ref.open,
                                        variant:  hasFallback ? 'secondary' : 'primary',
                                        style:    { width: '100%', justifyContent: 'center', marginBottom: '6px' },
                                    }, hasFallback
                                        ? __('Replace Image', 'generatepress-child')
                                        : __('+ Select Image', 'generatepress-child')
                                    );
                                },
                            })
                        ),

                        /* Remove button */
                        hasFallback && el(Button, {
                            onClick: function () {
                                set({ fallbackUrl: '', fallbackId: 0, fallbackAlt: '' });
                            },
                            variant:         'tertiary',
                            isDestructive:   true,
                            style:           { width: '100%', justifyContent: 'center' },
                        }, __('Remove Image', 'generatepress-child'))
                    ),

                    el(PanelBody, {
                        title:       __('⚙️ Layout', 'generatepress-child'),
                        initialOpen: false,
                    },
                        el(RangeControl, {
                            label:    __('Min Height — Desktop (px)', 'generatepress-child'),
                            value:    a.minHeight,
                            min:      200,
                            max:      1000,
                            step:     20,
                            onChange: function (v) { set({ minHeight: v }); },
                        }),
                        el(RangeControl, {
                            label:    __('Min Height — Tablet (px)', 'generatepress-child'),
                            help:     __('768 – 899 px', 'generatepress-child'),
                            value:    a.minHeightTablet,
                            min:      200,
                            max:      1000,
                            step:     20,
                            onChange: function (v) { set({ minHeightTablet: v }); },
                        }),
                        el(RangeControl, {
                            label:    __('Min Height — Mobile (px)', 'generatepress-child'),
                            help:     __('< 768 px', 'generatepress-child'),
                            value:    a.minHeightMobile,
                            min:      200,
                            max:      800,
                            step:     20,
                            onChange: function (v) { set({ minHeightMobile: v }); },
                        })
                    )
                ),

                /* ── Canvas ──────────────────────────────────────────────── */
                el('div', blockProps,

                    /* Background layer — shows fallback image or dark placeholder */
                    el('div', {
                        style: {
                            position:           'absolute',
                            inset:              0,
                            backgroundImage:    hasFallback ? 'url(' + a.fallbackUrl + ')' : 'none',
                            backgroundSize:     'cover',
                            backgroundPosition: (a.focalPoint.x * 100) + '% ' + (a.focalPoint.y * 100) + '%',
                            zIndex:             0,
                        },
                    }),

                    /* Status badges (top-left) */
                    el('div', {
                        style: {
                            position: 'absolute',
                            top:      '12px',
                            left:     '12px',
                            display:  'flex',
                            gap:      '6px',
                            zIndex:   20,
                        },
                    },
                        hasVideo
                            ? badge('▶ Vimeo ' + a.vimeoId, 'rgba(0,0,0,0.70)')
                            : badge('⚠ No Vimeo ID', '#b45309'),

                        hasFallback
                            ? badge('🖼 Fallback ✓', 'rgba(22,101,52,0.80)')
                            : badge('⚠ No fallback image', '#b45309')
                    ),

                    /* Dark gradient overlay so white text stays readable */
                    hasFallback && el('div', {
                        style: {
                            position:   'absolute',
                            inset:      0,
                            background: 'linear-gradient(to bottom, rgba(2,28,61,0.35) 0%, rgba(2,28,61,0.60) 100%)',
                            zIndex:     1,
                            pointerEvents: 'none',
                        },
                    }),

                    /* Setup hint — only when nothing configured yet */
                    !hasFallback && !hasVideo && el('div', {
                        style: {
                            position:       'absolute',
                            inset:          0,
                            display:        'flex',
                            flexDirection:  'column',
                            alignItems:     'center',
                            justifyContent: 'center',
                            gap:            '10px',
                            zIndex:         5,
                            color:          '#94a3b8',
                            pointerEvents:  'none',
                        },
                    },
                        el('span', { style: { fontSize: '40px' } }, '🎬'),
                        el('span', { style: { fontSize: '13px', fontWeight: '600' } },
                            __('Set image & Vimeo ID in the sidebar →', 'generatepress-child')
                        )
                    ),

                    /* Content layer — InnerBlocks */
                    el('div', {
                        style: {
                            position:     'relative',
                            zIndex:       10,
                            width:        '100%',
                            paddingBlock: '80px',
                            paddingInline: '32px',
                        },
                    },
                        el(InnerBlocks, {
                            template:     INNER_TEMPLATE,
                            templateLock: false,
                        })
                    )
                )
            );
        },

        /* ── Save ─────────────────────────────────────────────────────────── */
        // Dynamic block — PHP (render_callback) generates the front-end HTML.
        // InnerBlocks.Content ensures inner blocks are serialised to the DB.
        save: function () {
            return el(InnerBlocks.Content, null);
        },
    });

}(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n));
