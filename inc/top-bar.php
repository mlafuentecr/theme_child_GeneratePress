<?php
/**
 * Top bar above header (Expandable Search + Language)
 */

add_action('generate_before_header_content', function () {


?>

<nav class="signifi-top-bar">
  <div class="top-bar-wrap">

    <!-- LEFT (empty spacer) -->
    <div class="top-bar-left"></div>

    <!-- CENTER SEARCH (hidden by default) -->
    <div class="top-bar-search">
      <form
        role="search"
        method="get"
        class="signifi-search-form"
        action="<?php echo esc_url(home_url('/search')); ?>"
      >
    <input
        type="search"
        name="q"
        value="<?php echo esc_attr($_GET['q'] ?? ''); ?>"
        placeholder="Search…"
        required
    />

        <button type="submit" class="signifi-search-submit" aria-label="Submit search">
          <svg viewBox="0 0 512 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em">
            <path fill-rule="evenodd" clip-rule="evenodd"
              d="M208 48c-88.366 0-160 71.634-160 160s71.634 160 160 160 160-71.634 160-160S296.366 48 208 48zM0 208C0 93.125 93.125 0 208 0s208 93.125 208 208c0 48.741-16.765 93.566-44.843 129.024l133.826 134.018c9.366 9.379 9.355 24.575-.025 33.941-9.379 9.366-24.575 9.355-33.941-.025L337.238 370.987C301.747 399.167 256.839 416 208 416 93.125 416 0 322.875 0 208z">
            </path>
          </svg>
        </button>
      </form>
    </div>


    <!-- RIGHT ACTIONS -->
    <div class="top-bar-actions">
      <button class="top-search-toggle" aria-label="Open search">
        <svg viewBox="0 0 512 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em"><path fill-rule="evenodd" clip-rule="evenodd" d="M208 48c-88.366 0-160 71.634-160 160s71.634 160 160 160 160-71.634 160-160S296.366 48 208 48zM0 208C0 93.125 93.125 0 208 0s208 93.125 208 208c0 48.741-16.765 93.566-44.843 129.024l133.826 134.018c9.366 9.379 9.355 24.575-.025 33.941-9.379 9.366-24.575 9.355-33.941-.025L337.238 370.987C301.747 399.167 256.839 416 208 416 93.125 416 0 322.875 0 208z"></path></svg>
      </button>

          <div class="top-lang-switch">
            <a href="#" class="current-lang">EN</a>

            <div class="lang-dropdown">
              <a href="#">SP</a>
            </div>
          </div>

    </div>

  </div>
</nav>
<?php
});
