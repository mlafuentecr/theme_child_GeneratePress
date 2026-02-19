<?php
/**
 * Template Name: Use Case – Title Above Hero
 */

get_header(); ?>

<main id="primary" class="site-main">

  <?php while ( have_posts() ) : the_post(); ?>

    <!-- TÍTULO ARRIBA -->
    <header class="usecase-header">
      <h1 class="entry-title"><?php the_title(); ?></h1>
    </header>

    <!-- FEATURED IMAGE / HERO -->
    <?php if ( has_post_thumbnail() ) : ?>
      <section class="usecase-hero">
        <?php the_post_thumbnail('full'); ?>
      </section>
    <?php endif; ?>

    <!-- content -->
    <section class="usecase-content">
      <?php the_content(); ?>
    </section>

  <?php endwhile; ?>


</main>

<?php get_footer(); ?>
