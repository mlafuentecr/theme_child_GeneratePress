<?php 
  get_header(); 
  $disable_title = get_post_meta(get_the_ID(), '_generate-disable-headline', true);
?>

<main class="industry-single <?php echo ($disable_title !== 'true') ? 'headline-enabled' : ''; ?>">
  <?php
  while (have_posts()) :
    the_post();
  ?>


    <div class="industry-content">
      <?php the_content(); ?>
    </div>

  <?php endwhile; ?>
</main>

<?php get_footer(); ?>
