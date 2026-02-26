<?php
$tabs = signifi_search_post_types();
?>

<div class="search-tabs">
  <a href="#" class="tab active" data-post-type="all">All</a>

  <?php foreach ($tabs as $type => $label): ?>
    <a href="#" class="tab" data-post-type="<?php echo esc_attr($type); ?>">
      <?php echo esc_html($label); ?>
    </a>
  <?php endforeach; ?>
</div>
