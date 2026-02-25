<?php

class Blue_Flamingo_Default_Featured_Image {

	/**
	 * Hook everything
	 */
	public function __construct() {
		// set dfi meta key on every occasion.
		add_filter( 'get_post_metadata', array( $this, 'set_dfi_meta_key' ), 10, 4 );
		// display a default featured image.
		add_filter( 'post_thumbnail_html', array( $this, 'show_dfi' ), 20, 5 );
	}

	/**
	 * Add the dfi_id to the meta data if needed.
	 */
	public function set_dfi_meta_key( $null, $object_id, $meta_key, $single ) {
		// Only affect thumbnails on the frontend, do allow ajax calls.
		if ( ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) ) {
			return $null;
		}

		// Check only empty meta_key and '_thumbnail_id'.
		if ( ! empty( $meta_key ) && '_thumbnail_id' !== $meta_key ) {
			return $null;
		}

		// Check if this post type supports featured images.
		if ( ! post_type_supports( get_post_type( $object_id ), 'thumbnail' ) ) {
			return $null; // post type does not support featured images.
		}

		$pt = get_post_type( $object_id );
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		$allowed_post_type = isset( $bfps_o['default_featured_image_post_types'] ) ? $bfps_o['default_featured_image_post_types'] : false;

		if( $allowed_post_type ){
			$apt = array();
			foreach( $allowed_post_type as $posttype => $enabled ){
				$apt[] = $posttype;
			}
			if( ! in_array($pt,$apt) ){
				return $null;
			}
		}

		// Get current Cache.
		$meta_cache = wp_cache_get( $object_id, 'post_meta' );

		/**
		 * Empty objects probably need to be initiated.
		 *
		 * @see get_metadata() in /wp-includes/meta.php
		 */
		if ( ! $meta_cache ) {
			$meta_cache = update_meta_cache( 'post', array( $object_id ) );
			if ( isset( $meta_cache[ $object_id ] ) ) {
				$meta_cache = $meta_cache[ $object_id ];
			} else {
				$meta_cache = array();
			}
		}

		// Is the _thumbnail_id present in cache?
		if ( ! empty( $meta_cache['_thumbnail_id'][0] ) ) {
			return $null; // it is present, don't check anymore.
		}

		// Get the Default Featured Image ID.
		if( !empty( $bfps_o['default_featured_image'] ) ){
			$df = $bfps_o['default_featured_image'];
		}else{
			$df = '';
		}

		// Set the dfi in cache.
		$meta_cache['_thumbnail_id'][0] = apply_filters( 'dfi_thumbnail_id', $df, $object_id );
		wp_cache_set( $object_id, $meta_cache, 'post_meta' );

		return $null;
	}

	/**
	 * Display the buttons and a preview on the media settings page.
	 */
	public function settings_html() {
		$value = get_option( 'dfi_image_id' );
		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		$rm_btn_class = 'button button-disabled';
		if ( !empty( $bfps_o['default_featured_image'] ) ) {
			echo $this->preview_image( $bfps_o['default_featured_image'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$rm_btn_class = 'button';
		}
		?>
		<input id="dfi_id" type="hidden" value="<?php echo (!empty($bfps_o['default_featured_image'])) ? $bfps_o['default_featured_image'] : ''; ?>" name="blueflamingo_plugin_options_settings[default_featured_image]"/>
		<a id="dfi-set-dfi" class="button" title="Select default featured image" href="#" style="float: left;margin-right: 4px;">Select</a>
		<div>
			<a id="dfi-no-fdi" class="<?php echo esc_attr( $rm_btn_class ); ?>" title="Remove" href="#">Remove</a>
		</div><br/>
		<code>Assign to Post Types</code>
		<p></p>
		<?php
        // Debug output removed for production
		$args = array(
		   'public'   => true,
		);
		$output = 'objects'; // 'names' or 'objects' (default: 'names')
		$operator = 'or'; // 'and' or 'or' (default: 'and')
		$post_types = get_post_types( $args, $output, $operator );
		if ( $post_types ) { // If there are any custom public post types.
			foreach ( $post_types as $slug => $post_type ) { ?>
				<label for="allowed-post-types-for-default-featured-image-<?php echo sanitize_title($slug); ?>"><input id="allowed-post-types-for-default-featured-image-<?php echo sanitize_title($slug); ?>" class="checkALL_AFIPT" type="checkbox" name="blueflamingo_plugin_options_settings[default_featured_image_post_types][<?php echo $slug; ?>]" value="1" <?php checked( 1, isset($bfps_o['default_featured_image_post_types'][$slug]), true ); ?> /> <?php echo ucfirst($post_type->labels->name ); ?> <small>( slug: <?php echo $slug; ?> )</small></label><br/>
			<?php }
		}
		echo '<br/><label><input type="checkbox" name="checkALLAFIPT">Check all</label>';
		echo '<p>( Assigned to all post types if none of above post types are selected )</p>';
	}

	/**
	 * Is the given input a valid image.
	 */
	public function input_validation( $thumbnail_id ) {
		if ( wp_attachment_is_image( $thumbnail_id ) ) {
			return $thumbnail_id;
		}

		return false;
	}

	/**
	 * Get an image and wrap it in a div
	 */
	public function preview_image( $image_id ) {
		if ( !empty( $image_id ) ) {
			$output  = '<div id="preview-image">';
			$output .= wp_get_attachment_image( $image_id, array( 128, 128 ), true );
			$output .= '</div>';
			return $output;
		}else{
			return;
		}
	}

	/**
	 * Set a default featured image if it is missing
	 */
	public function show_dfi( $html, $post_id, $post_thumbnail_id, $size, $attr ) {

		$bfps_o = get_option( 'blueflamingo_plugin_options_settings' );
		$default_thumbnail_id = ( !empty( $bfps_o['default_featured_image'] ) ) ? $bfps_o['default_featured_image'] : ''; // select the default thumb.

		// if an image is set return that image.
		if ( (int) $default_thumbnail_id !== (int) $post_thumbnail_id ) {
			return $html;
		}

		if ( isset( $attr['class'] ) ) {
			$attr['class'] .= ' default-featured-img';
		} else {
			$size_class = $size;
			if ( is_array( $size_class ) ) {
				$size_class = 'size-' . implode( 'x', $size_class );
			}
			// attachment-$size is a default class `wp_get_attachment_image` would otherwise add. It won't add it if there are classes already there.
			$attr = array( 'class' => "attachment-{$size_class} default-featured-img" );
		}

		$html = wp_get_attachment_image( $default_thumbnail_id, $size, false, $attr );
		$html = apply_filters( 'dfi_thumbnail_html', $html, $post_id, $default_thumbnail_id, $size, $attr );

		return $html;
	}
}

new Blue_Flamingo_Default_Featured_Image();