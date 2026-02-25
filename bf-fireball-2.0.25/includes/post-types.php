<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'Blue_Flamingo_Post_Types' ) ) :

class Blue_Flamingo_Post_Types {

	function __construct() {

		define( 'bf_notes_post_type', 'blue-flamingo-notes' );
		define( 'bf_shortcode_post_type', 'bf-shortcodes' );

		// Registers Notes Post type
		$this->bf_register_pt();

		// Add colowmn to custom Notes post type
		//add_filter('manage_' .bf_notes_post_type. '_posts_columns', array($this, 'bf_posts_columns') );
		//add_action('manage_' .bf_notes_post_type. '_posts_custom_column', array($this, 'bf_post_columns_data'), 10, 2);

		// Add meta box for new Notes post type 
		add_action( 'add_meta_boxes_'.bf_notes_post_type, array($this, 'bf_metabox') );

		// Save action
		add_action( 'save_post', array($this, 'bf_save_value') );

		// Registers Shortcode Post type
		$this->bf_register_pt_shortcode();

		// Add colowmn to custom Shortcode post type
		add_filter('manage_' .bf_shortcode_post_type. '_posts_columns', array($this, 'bf_shortcode_posts_columns') );
		add_action('manage_' .bf_shortcode_post_type. '_posts_custom_column', array($this, 'bf_shortcode_post_columns_data'), 10, 2);

		// Add meta box for new Shortcode post type 
		add_action('add_meta_boxes_'.bf_shortcode_post_type, array($this, 'bf_shortcode_metabox') );

		// Post updated message
		add_filter('post_updated_messages', array($this, 'bf_updated_post_msg'));
		
		// Enable shortcodes in bf notes
		add_filter('the_content', 'do_shortcode');
		// Add support for iframe and video embeds in admin notes
		add_filter('content_save_pre', 'wp_filter_post_kses');
		add_filter('content_filtered_save_pre', 'wp_filter_post_kses');
		// Add 'View' button to the list of admin notes
		add_filter('post_row_actions', 'add_blue_flamingo_notes_view_button', 10, 2);
		// Register custom page for viewing admin notes
		add_action('admin_menu', 'register_bf_note_view_page');
		// Register categories taxonomy for notes
		add_action( 'init', 'custom_notes_category' );

	}

	function bf_register_pt() {

		$lbls = array(
			'name'                 	=> __('Notes', 'blue-flamingo-text'),
			'singular_name'        	=> __('Blue Flamingo Note', 'blue-flamingo-text'),
			'add_new'              	=> __('Add a Note', 'blue-flamingo-text'),
			'add_new_item'         	=> __('Add New Note', 'blue-flamingo-text'),
			'edit_item'            	=> __('Edit Note', 'blue-flamingo-text'),
			'new_item'             	=> __('New Note', 'blue-flamingo-text'),
			'view_item'            	=> __('View Note', 'blue-flamingo-text'),
			'search_items'         	=> __('Search Note', 'blue-flamingo-text'),
			'not_found'            	=> __('No Note Found', 'blue-flamingo-text'),
			'not_found_in_trash'   	=> __('No Note Found in Trash', 'blue-flamingo-text'),
			'parent_item_colon'    	=> '',
			'featured_image'		=> __('Note Featured Image', 'blue-flamingo-text'),
			'set_featured_image'	=> __('Set Featured Image', 'blue-flamingo-text'),
			'remove_featured_image'	=> __('Remove Image', 'blue-flamingo-text'),
			'menu_name'           	=> __('Note', 'blue-flamingo-text')
		);

		$pg_slider_args = array(
			'labels'				=> $lbls,
			'public'              	=> false,
			'show_ui'             	=> true,
			'query_var'           	=> false,
			'rewrite'             	=> false,
			'capability_type'     	=> 'post',
			'hierarchical'        	=> false,
			'menu_icon'				=> '',
			'menu_position'        	=> 100,
			'supports'            	=> array('title','author','editor'),
			'capabilities' => [
				'create_posts' => 'manage_options',
				'edit_posts' => 'manage_options',
				'edit_others_posts' => 'manage_options',
				'delete_posts' => 'manage_options',
				'delete_others_posts' => 'manage_options',
				'read_private_posts' => 'manage_options',
				'edit_post' => 'manage_options',
				'delete_post' => 'manage_options',
				'read_post' => 'manage_options',
			],
		);

		// Register Post Type
		register_post_type( bf_notes_post_type, $pg_slider_args );
	}

	function bf_updated_post_msg( $messages ) {

		global $post, $post_ID;

		$messages[bf_notes_post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Note updated.', 'blue-flamingo-text' ) ),
			2 => __( 'Custom field updated.', 'blue-flamingo-text' ),
			3 => __( 'Custom field deleted.', 'blue-flamingo-text' ),
			4 => __( 'Note updated.', 'blue-flamingo-text' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Note restored to revision from %s', 'blue-flamingo-text' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Note published.', 'blue-flamingo-text' ) ),
			7 => __( 'Note saved.', 'blue-flamingo-text' ),
			8 => sprintf( __( 'Note submitted.', 'blue-flamingo-text' ) ),
			9 => sprintf( __( 'Note scheduled for: <strong>%1$s</strong>.', 'blue-flamingo-text' ),
			  date_i18n( __( 'M j, Y @ G:i', 'blue-flamingo-text' ), strtotime( $post->post_date ) ) ),
			10 => sprintf( __( 'Note draft updated.', 'blue-flamingo-text' ) ),
		);

		$messages[bf_shortcode_post_type] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( __( 'Shortcode updated.', 'blue-flamingo-text' ) ),
			2 => __( 'Custom field updated.', 'blue-flamingo-text' ),
			3 => __( 'Custom field deleted.', 'blue-flamingo-text' ),
			4 => __( 'Shortcode updated.', 'blue-flamingo-text' ),
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Shortcode restored to revision from %s', 'blue-flamingo-text' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __( 'Shortcode published.', 'blue-flamingo-text' ) ),
			7 => __( 'Shortcode saved.', 'blue-flamingo-text' ),
			8 => sprintf( __( 'Shortcode submitted.', 'blue-flamingo-text' ) ),
			9 => sprintf( __( 'Shortcode scheduled for: <strong>%1$s</strong>.', 'blue-flamingo-text' ),
			  date_i18n( __( 'M j, Y @ G:i', 'blue-flamingo-text' ), strtotime( $post->post_date ) ) ),
			10 => sprintf( __( 'Shortcode draft updated.', 'blue-flamingo-text' ) ),
		);

		return $messages;
	}

	function bf_posts_columns( $columns ) {
	    $new_columns['bf_note']	= __('Note', 'blue-flamingo-text');
	    $columns = $this->bf_add_array( $columns, $new_columns, 2, true );
	    return $columns;
	}

	function bf_post_columns_data( $column, $post_id ) {
		global $post;
	    switch ($column) {
	    	case 'bf_note':
	    		echo get_the_content();
	    		break;
		}
	}

	function bf_metabox() {

		add_meta_box(
			'pg-post-help',
			__( 'Sticky Note', 'blue-flamingo-text' ),
			array( $this, 'bf_shortcode_content' ),
			bf_notes_post_type,
			'side',
			'default'
		);

	}

	function bf_add_array(&$array, $value, $index, $from_last = false) {
		if( is_array($array) && is_array($value) ) {
			if( $from_last ) {
				$total_count    = count($array);
				$index          = (!empty($total_count) && ($total_count > $index)) ? ($total_count-$index): $index;
			}
			$split_arr  = array_splice($array, max(0, $index));
			$array      = array_merge( $array, $value, $split_arr);
		}
		return $array;
	}

	function bf_shortcode_content( $post ) {
		$notesData 			=	get_post_meta( $post->ID, '_bf_notes_data', true );
		$enable_lightbox	=	!empty($notesData) ? $notesData['sticky']['enable'] : ''; ?>
		<div>
			<table class="form-table">
				<tbody>
					<tr class="enable_lightbox">
						<th scope="row">
							<label for="enable_lightbox">Sticky?</label>
						</th>
						<td>
							<input type="checkbox" id="enable_lightbox" name="_bf_notes[lightbox_enable]" <?php checked( 'on', esc_attr( $enable_lightbox ), true ); ?>>
						</td>
					</tr>
				</tbody>
			</table>
		</div><?php
	}

	function bf_save_value( $post_id ) {

		global $post_type;

		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( ! isset( $_POST['post_ID'] ) || $post_id != $_POST['post_ID'] ) || ( $post_type !=  bf_notes_post_type ) ) {
		  return $post_id;
		}

		$data = get_post_meta( $post_id, '_bf_notes_data', true );
		$notesData = ( !empty($data) ) ? $data : array();

		$notesData['sticky']['enable'] = sanitize_text_field( $_POST['_bf_notes']['lightbox_enable'] );

		update_post_meta( $post_id, '_bf_notes_data', $notesData );

	}


	function bf_register_pt_shortcode() {

		$lbls = array(
			'name'                 	=> __('Shortcodes', 'blue-flamingo-text'),
			'singular_name'        	=> __('Blue Flamingo Shortcode', 'blue-flamingo-text'),
			'add_new'              	=> __('Add a Shortcode', 'blue-flamingo-text'),
			'add_new_item'         	=> __('Add New Shortcode', 'blue-flamingo-text'),
			'edit_item'            	=> __('Edit Shortcode', 'blue-flamingo-text'),
			'new_item'             	=> __('New Shortcode', 'blue-flamingo-text'),
			'view_item'            	=> __('View Shortcode', 'blue-flamingo-text'),
			'search_items'         	=> __('Search Shortcode', 'blue-flamingo-text'),
			'not_found'            	=> __('No Shortcode Found', 'blue-flamingo-text'),
			'not_found_in_trash'   	=> __('No Shortcode Found in Trash', 'blue-flamingo-text'),
			'parent_item_colon'    	=> '',
			'featured_image'		=> __('Shortcode Featured Image', 'blue-flamingo-text'),
			'set_featured_image'	=> __('Set Featured Image', 'blue-flamingo-text'),
			'remove_featured_image'	=> __('Remove Image', 'blue-flamingo-text'),
			'menu_name'           	=> __('Shortcode', 'blue-flamingo-text')
		);

		$pg_slider_args = array(
			'labels'				=> $lbls,
			'public'              	=> false,
			'show_ui'             	=> true,
			'query_var'           	=> false,
			'rewrite'             	=> false,
			'capability_type'     	=> 'post',
			'hierarchical'        	=> false,
			'menu_icon'				=> '',
			'menu_position'        	=> 100,
			'supports'            	=> array('title','editor'),
		);

		// Register Post Type
		register_post_type( bf_shortcode_post_type, $pg_slider_args );
	}

	function bf_shortcode_posts_columns( $columns ) {
	    $new_columns['bf_shortcode']	= __('Shortcode', 'blue-flamingo-text');
	    $columns = $this->bf_add_array( $columns, $new_columns, 1, true );
	    return $columns;
	}

	function bf_shortcode_post_columns_data( $column, $post_id ) {
		global $post;
	    switch ($column) {
	    	case 'bf_shortcode':
				echo '<code>[bf-shortcode id="'.$post->ID.'"]</code><p></p><code>&lt;?php echo do_shortcode(\'[bf-shortcode id="'.$post->ID.'"]\'); ?&gt;</code>';
	    		break;
		}
	}

	function bf_shortcode_metabox() {

		add_meta_box(
			'bf-post-shortcode',
			__( 'Shortcode', 'blue-flamingo-text' ),
			array( $this, 'bf_shortcode_pt_content' ),
			bf_shortcode_post_type,
			'side',
			'default'
		);

	}

	function bf_shortcode_pt_content( $post ) {
		echo '<code>[bf-shortcode id="'.$post->ID.'"]</code><p></p><code>&lt;?php echo do_shortcode(\'[bf-shortcode id="'.$post->ID.'"]\'); ?&gt;</code>';
	}

}

new Blue_Flamingo_Post_Types();

endif;