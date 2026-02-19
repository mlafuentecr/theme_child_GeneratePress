<?php
/* ============================================================
 * FRONTEND ASSETS
 * ============================================================ */
add_action('wp_enqueue_scripts','signifi_enqueue_frontend_assets');

function signifi_enqueue_frontend_assets() {

  /* Fonts */
  wp_enqueue_style('fonts-raleway','https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap',[],null);
  wp_enqueue_style('fonts-barlow-condensed','https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&display=swap',[],null);
  wp_enqueue_style('fonts-barlow','https://fonts.googleapis.com/css2?family=Barlow:wght@300;400;500;600;700&display=swap',[],null);

  /* Global CSS */
  wp_enqueue_style('gp-vars',ML_CHILD_URI.'/assets/css/variables.css');
  wp_enqueue_style('gp-menu',ML_CHILD_URI.'/assets/css/menu.css');
  wp_enqueue_style('gp-menu-search',ML_CHILD_URI.'/assets/css/topbar_search.css');
  wp_enqueue_style('gp-style',ML_CHILD_URI.'/style.css');

  /* Global JS */
  wp_enqueue_script('gp-main',ML_CHILD_URI.'/assets/js/main.js',[],null,true);

  /* ==========================================================
   * USE CASES PAGE ONLY
   * ========================================================== */
  if ( is_page_template('page-use-cases.php') || is_page(1032) || is_page('cases') ) {
    wp_enqueue_style('use-cases',ML_CHILD_URI.'/assets/css/use-cases-posttype.css',[],'1.0');
    wp_enqueue_script('use-cases-filter',ML_CHILD_URI.'/assets/js/use-cases-filter.js',['jquery'],'1.0',true);
    wp_localize_script('use-cases-filter','UseCasesAjax',['ajaxurl' => admin_url('admin-ajax.php')]);
  }
 /* ==========================================================
   * USE CASES Pop up is global
   * ========================================================== */
  wp_enqueue_style('use-cases-slider',ML_CHILD_URI.'/assets/css/patterns/use-cases-slider.css',[],'1.0');
  wp_enqueue_script('use-cases-modal',ML_CHILD_URI.'/assets/js/use-cases-modal.js',[],'1.0',true);
  wp_localize_script('use-cases-modal','UseCasesAjax',['ajaxurl' => admin_url('admin-ajax.php')]);

  /* ==========================================================
   * SEARCH RESULTS PAGE ONLY
   * ========================================================== */
  if ( is_search() ) {

    $base_uri = ML_CHILD_URI.'/inc/search_result/assets';

    wp_enqueue_style('search-results-css',$base_uri.'/search-results.css',[],'1.0');
    wp_enqueue_script('search-results-js',$base_uri.'/search-results.js',[],'1.0',true);

    wp_localize_script('search-results-js','search_ajax',['ajax_url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('search_load_more_nonce')]);
  }
}
/* ============================================================
 * EDITOR STYLES
 * ============================================================ */
add_action('after_setup_theme', function() {
  add_theme_support('editor-styles');
  add_editor_style('/assets/css/variables.css');

  });