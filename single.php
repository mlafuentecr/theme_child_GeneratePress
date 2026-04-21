<?php
/**
 * Single post template.
 *
 * Keep the GeneratePress hook structure intact so Elements content templates
 * can replace the default single-post output when their display rules match.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<div <?php generate_do_attr( 'content' ); ?>>
	<main <?php generate_do_attr( 'main' ); ?>>
		<?php
		do_action( 'generate_before_main_content' );

		if ( generate_has_default_loop() ) {
			while ( have_posts() ) {
				the_post();
				generate_do_template_part( 'single' );
			}
		}

		do_action( 'generate_after_main_content' );
		?>
	</main>
</div>

<?php
do_action( 'generate_after_primary_content_area' );

generate_construct_sidebars();

get_footer();
