/**
 * GP Animation — Block Editor Extension.
 *
 * Adds an "Animation" panel to every block's right sidebar.
 * Applies the chosen animation class + data-animate-delay to the saved HTML,
 * where gp-animations.js picks them up via IntersectionObserver.
 *
 * No JSX / no build step. Uses window.wp.* APIs exactly like popup-block-editor.js.
 */
(function (hooks, compose, blocks, blockEditor, components, element, i18n) {
    'use strict';

    var addFilter                  = hooks.addFilter;
    var createHigherOrderComponent = compose.createHigherOrderComponent;

    var el       = element.createElement;
    var Fragment = element.Fragment;
    var __       = i18n.__;

    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody         = components.PanelBody;
    var SelectControl     = components.SelectControl;
    var RangeControl      = components.RangeControl;
    var ToggleControl     = components.ToggleControl;

    // Animation list injected from PHP via wp_localize_script.
    // Falls back to the full list if the localized object is missing.
    var gpData = window.gpAnimations || {};
    var ANIMATION_OPTIONS = gpData.animations || [
        { value: '',           label: '\u2014 None \u2014' },
        { value: 'fade-up',    label: 'Fade Up'            },
        { value: 'fade-down',  label: 'Fade Down'          },
        { value: 'fade-left',  label: 'Fade Left'          },
        { value: 'fade-right', label: 'Fade Right'         },
        { value: 'zoom-in',    label: 'Zoom In'            },
        { value: 'zoom-out',   label: 'Zoom Out'           },
    ];

    // Class names only (used to scrub stale manual entries from className)
    var ANIMATION_CLASSES = ANIMATION_OPTIONS
        .map(function (o) { return o.value; })
        .filter(Boolean);

    /* ====================================================================
     * 1. Register gpAnimation + gpAnimationDelay on every block type.
     * ==================================================================== */
    addFilter(
        'blocks.registerBlockType',
        'gp/animation-attributes',
        function (settings) {
            if (!settings.attributes) {
                settings.attributes = {};
            }
            settings.attributes = Object.assign({}, settings.attributes, {
                gpAnimation:       { type: 'string',  default: ''    },
                gpAnimationDelay:  { type: 'number',  default: 0     },
                gpAnimationRepeat: { type: 'boolean', default: false },
            });
            return settings;
        }
    );

    /* ====================================================================
     * 2. Inject "Animation" panel into every block's InspectorControls.
     * ==================================================================== */
    var withAnimationControls = createHigherOrderComponent(function (BlockEdit) {
        return function (props) {
            var attributes    = props.attributes;
            var setAttributes = props.setAttributes;

            var currentAnim   = attributes.gpAnimation       || '';
            var currentDelay  = attributes.gpAnimationDelay  || 0;
            var currentRepeat = attributes.gpAnimationRepeat || false;

            function onChangeAnimation(newAnim) {
                // Strip any animation class previously typed manually in
                // "Additional CSS Classes" to avoid duplicates.
                var cleanedClassName = (attributes.className || '')
                    .split(' ')
                    .filter(function (cls) {
                        return cls && ANIMATION_CLASSES.indexOf(cls) === -1;
                    })
                    .join(' ')
                    .trim();

                setAttributes({
                    gpAnimation:      newAnim,
                    className:        cleanedClassName || undefined,
                    gpAnimationDelay: newAnim ? currentDelay : 0,
                });
            }

            return el(Fragment, null,

                // Pass through the original block edit component unchanged.
                el(BlockEdit, props),

                // Append our panel to the sidebar.
                el(InspectorControls, null,
                    el(PanelBody, {
                        title:       __('Animation', 'generatepress-child'),
                        initialOpen: false,
                    },

                        el(SelectControl, {
                            label:    __('Entrance animation', 'generatepress-child'),
                            help:     __('Triggered when the element enters the viewport.', 'generatepress-child'),
                            value:    currentAnim,
                            options:  ANIMATION_OPTIONS,
                            onChange: onChangeAnimation,
                        }),

                        // Delay slider — only visible when an animation is chosen.
                        currentAnim
                            ? el(RangeControl, {
                                label:    __('Delay (ms)', 'generatepress-child'),
                                help:     __('Wait before the animation starts.', 'generatepress-child'),
                                value:    currentDelay,
                                min:      0,
                                max:      1500,
                                step:     50,
                                onChange: function (v) {
                                    setAttributes({ gpAnimationDelay: v || 0 });
                                },
                            })
                            : null,

                        // Repeat toggle — only visible when an animation is chosen.
                        currentAnim
                            ? el(ToggleControl, {
                                label:    __('Replay each time in viewport', 'generatepress-child'),
                                help:     __('Re-triggers the animation every time the element scrolls into view.', 'generatepress-child'),
                                checked:  currentRepeat,
                                onChange: function (v) {
                                    setAttributes({ gpAnimationRepeat: v });
                                },
                            })
                            : null
                    )
                )
            );
        };
    }, 'withAnimationControls');

    addFilter('editor.BlockEdit', 'gp/animation-controls', withAnimationControls);

    /* ====================================================================
     * 3. Write animation class + data-attribute into the SAVED HTML.
     *    gp-animations.js reads these on the frontend.
     * ==================================================================== */
    addFilter(
        'blocks.getSaveContent.extraProps',
        'gp/animation-save-props',
        function (extraProps, blockType, attributes) {
            if (!attributes.gpAnimation) {
                return extraProps;
            }

            // Merge animation class into whatever className already exists.
            var existing = extraProps.className || '';
            extraProps.className = [existing, attributes.gpAnimation]
                .filter(Boolean)
                .join(' ');

            // Write delay only when set (gp-animations.js reads data-animate-delay).
            if (attributes.gpAnimationDelay > 0) {
                extraProps['data-animate-delay'] = String(attributes.gpAnimationDelay);
            }

            // Write repeat flag (gp-animations.js keeps observing when present).
            if (attributes.gpAnimationRepeat) {
                extraProps['data-animate-repeat'] = '1';
            }

            return extraProps;
        }
    );

    /* ====================================================================
     * 4. Mirror the class on the EDITOR canvas wrapper.
     *    This is a visual indicator only — the animation does not play
     *    inside the editor, but the class is visible in DevTools.
     * ==================================================================== */
    var withAnimationEditorClass = createHigherOrderComponent(function (BlockListBlock) {
        return function (props) {
            var anim   = props.attributes.gpAnimation;
            var delay  = props.attributes.gpAnimationDelay;
            var repeat = props.attributes.gpAnimationRepeat;

            if (!anim) {
                return el(BlockListBlock, props);
            }

            var wrapperProps  = Object.assign({}, props.wrapperProps || {});
            var existingClass = wrapperProps.className || '';

            wrapperProps.className = [existingClass, anim]
                .filter(Boolean)
                .join(' ');

            if (delay > 0) {
                wrapperProps['data-animate-delay'] = String(delay);
            }

            if (repeat) {
                wrapperProps['data-animate-repeat'] = '1';
            }

            return el(BlockListBlock, Object.assign({}, props, {
                wrapperProps: wrapperProps,
            }));
        };
    }, 'withAnimationEditorClass');

    addFilter('editor.BlockListBlock', 'gp/animation-editor-class', withAnimationEditorClass);

}(
    window.wp.hooks,
    window.wp.compose,
    window.wp.blocks,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.element,
    window.wp.i18n
));
