( function ( wp ) {
  /**
   * WordPress dependencies
   */
  const { registerBlockType } = wp.blocks;
  const { createElement: el, Fragment } = wp.element;
  const {
    useBlockProps,
    RichText,
    InspectorControls,
  } = wp.blockEditor;
  const {
    PanelBody,
    TextControl,
    ToggleControl,
    SelectControl,
  } = wp.components;

  /**
   * Register SIGNIFI block collection
   */
  wp.domReady( () => {
    wp.blocks.registerBlockCollection( 'signifi', {
      title: 'SIGNIFI',
      icon: 'star-filled',
    } );
  } );

  /**
   * Normalize URL
   * Adds https:// if missing
   */
  const normalizeUrl = ( url ) => {
    if ( ! url ) return '';
    if ( /^https?:\/\//i.test( url ) ) return url;
    return `https://${url}`;
  };

  /**
   * Register the block
   */
  registerBlockType( 'signifi/react-button', {
    title: 'Button',
    icon: 'admin-links',
    category: 'text',
    collection: 'signifi',

    /**
     * Block attributes
     */
    attributes: {
      content: {
        type: 'string',
        default: '',
      },
      url: {
        type: 'string',
        default: '',
      },
      target: {
        type: 'string',
        default: '_self',
      },
      variant: {
        type: 'string',
        default: 'primary',
      },
    },

    /**
     * Inserter preview
     */
    example: {
      attributes: {
        content: 'Click me',
        url: 'https://www.blueflamingo.co.uk/',
        target: '_blank',
        variant: 'primary',
      },
    },

    /**
     * Editor UI
     */
    edit( { attributes, setAttributes } ) {
      const { content, url, target, variant } = attributes;

      const buttonClass = `button-signifi-${variant}`;

      const blockProps = useBlockProps( {
        tagName: 'a',
        className: buttonClass,
        href: normalizeUrl( url ) || '#',
        target,
        rel: target === '_blank' ? 'noopener noreferrer' : undefined,

        // 🔑 Prevent navigation inside the editor
        onClick: ( event ) => {
          event.preventDefault();
        },
      } );

      return el(
        Fragment,
        {},

        /**
         * Sidebar controls
         */
        el(
          InspectorControls,
          {},
          el(
            PanelBody,
            { title: 'Button Settings', initialOpen: true },

            el( TextControl, {
              label: 'URL',
              value: url,
              placeholder: 'https://example.com',
              onChange: ( value ) =>
                setAttributes( { url: value } ),
            } ),

            el( ToggleControl, {
              label: 'Open in new tab',
              checked: target === '_blank',
              onChange: ( value ) =>
                setAttributes( {
                  target: value ? '_blank' : '_self',
                } ),
            } ),

            el( SelectControl, {
              label: 'Button Style',
              value: variant,
              options: [
                { label: 'Primary', value: 'primary' },
                { label: 'Secondary', value: 'secondary' },
              ],
              onChange: ( value ) =>
                setAttributes( { variant: value } ),
            } )
          )
        ),

        /**
         * Block content (editor)
         */
        el(
          'a',
          blockProps,
          el( RichText, {
            value: content,
            onChange: ( value ) =>
              setAttributes( { content: value } ),
            placeholder: 'Button text…',
            allowedFormats: [],
          } )
        )
      );
    },

    /**
     * Frontend output
     */
    save( { attributes } ) {
      const { content, url, target, variant } = attributes;

      const buttonClass = `button-signifi-${variant}`;

      const blockProps = useBlockProps.save( {
        tagName: 'a',
        className: buttonClass,
        href: normalizeUrl( url ) || '#',
        target,
        rel: target === '_blank' ? 'noopener noreferrer' : undefined,
      } );

      return el(
        'a',
        blockProps,
        el( RichText.Content, {
          value: content,
        } )
      );
    },
  } );
} )( window.wp );
