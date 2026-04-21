# GeneratePress Child Theme — Blue Flamingo

Child theme built on GeneratePress. Most custom logic lives in `inc/` and is loaded from `functions.php`.

## Search Shortcodes Quick Start

Use these two shortcodes together:

```text
[gp_search_bar]
[gp_search_bar variant="icon"]
[post_search_result]
```

What each shortcode does:

- `[gp_search_bar]`: renders the search input
- `[gp_search_bar variant="icon"]`: renders a search icon button that expands into the search field on click
- `[post_search_result]`: renders the results layout

Use the `icon` variant in headers, nav bars, or compact UI areas where you want to start with only the search icon visible.

Recommended setup:

1. Create a WordPress page such as `Search`
2. Add `[post_search_result]` to that page
3. Go to `Appearance > Blue Flamingo Settings > Pages & Search`
4. Select `Redirect to results page`
5. Choose the page you created
6. Place `[gp_search_bar]` anywhere you want the search field

Available search modes:

- `Live search dropdown`: shows AJAX results directly below the input
- `Redirect to results page`: sends visitors to a selected page that contains `[post_search_result]`

## Admin Panel

Settings live under:

`Appearance > Blue Flamingo Settings`

### Site Info

Stores:

- `Live Site URL`
- `Staging Site URL`

These values are used by the theme to detect the current environment and power features like:

- Stripe test mode
- WP Simple Pay test mode
- Email Redirect

### Core Features

This tab is for global cleanup and layout behavior.

Includes:

- WordPress cleanup toggles such as emojis, embeds, dashicons, feeds, XML-RPC, and REST API controls
- GeneratePress header/footer removal toggles
- Default layout controls for sidebar, content container, footer widgets, and title visibility

Use this tab when you want a site-wide default behavior rather than page-by-page configuration.

### Options

This tab groups operational tools and utility settings.

Includes:

- Stripe test mode
- WP Simple Pay test mode
- Show all meta fields
- Disable password-change emails
- User registration date column
- Duplicate Pages & Posts
- Default Featured Image
- WhatConverts ID
- Admin and plugin restriction tools

### Email Redirect

Redirects outgoing mail to a safe inbox for testing.

Available controls:

- Redirect on staging/development
- Redirect on production
- Redirect email address

When active, outgoing mail gets:

- recipient replaced with the configured test email
- `[TEST]` added to the subject
- original recipient added into the message for reference

### Analytics

Controls front-end tracking output.

Includes:

- GA4 ID
- GTM container ID
- script position (`Head` or `Footer`)
- include logged-in users
- WhatConverts support via the Options tab

### 404 Page

Lets you pick a normal WordPress page to be used as the custom 404 template.

### Search

Controls how the theme search shortcodes behave.

See `Search Shortcodes Quick Start` above for the recommended setup and shortcode usage.

### Cache Buster

Use this tab when CSS, JS, or images seem cached.

Includes:

- manual CSS/JS version counter
- optional image URL versioning
- documentation notes for clearing CDN/firewall cache

### Notes

Internal note system for the team.

Includes:

- role-based visibility
- editable color-coded notes
- dashboard notice output

### WebP

Controls automatic conversion of uploaded images to WebP.

Includes:

- enable/disable conversion
- max width
- max height
- quality

## Important Shortcodes

### Search Bar

```text
[gp_search_bar]
```

Optional attributes:

```text
[gp_search_bar placeholder="Search..." post_types="post,page" limit="5"]
```

### Search Results

```text
[post_search_result]
```

### Modal System

```text
[gp_modal id="x" size="md"]...[/gp_modal]
[gp_modal_trigger id="x"]Open[/gp_modal_trigger]
```

## Notable Modules

- `inc/admin-settings.php`: admin UI and settings registration
- `inc/helpers.php`: environment detection helpers and shared theme helpers
- `inc/cleanup.php`: WordPress and front-end cleanup toggles
- `inc/options-runtime.php`: Options tab runtime behavior
- `inc/email-redirect.php`: outgoing mail redirect logic
- `inc/analytics.php`: GA4, GTM, and WhatConverts output
- `inc/search.php`: `[gp_search_bar]`
- `inc/search_result/index.php`: `[post_search_result]`
- `inc/updater.php`: manifest-based theme updates from a public repo
- `inc/layout-defaults.php`: GeneratePress layout defaults
- `inc/duplicate-content.php`: duplicate posts/pages
- `inc/notes.php`: internal notes and dashboard notices
- `inc/rest-api.php`: JSON Basic Auth runtime

## Developer Onboarding

This theme uses `functions.php` as a bootstrap file. Most implementation lives in modular PHP files under `inc/`, reusable front-end assets under `assets/css` and `assets/js`, and legacy or project-specific shortcodes under `shortcodes/`.

High-level characteristics:

- `functions.php` bootstraps constants, includes modules, and wires global theme behavior
- most new feature work should happen in `inc/` rather than in `functions.php`
- reusable theme styles and scripts live in `assets/`
- search, modal, documents, leadership, counters, and news features are split into dedicated modules or shortcode folders
- the admin settings UI lives under `Appearance > Blue Flamingo Settings`
- several legacy option keys from older Blue Flamingo / Fireball setups are intentionally preserved for migration compatibility

### Bootstrap Flow

Main entry point:

- `functions.php`

What it does:

- defines constants such as `GP_CHILD_VERSION`, `GP_CHILD_DIR`, and `GP_CHILD_URI`
- defines brand identifiers like `GP_CHILD_BRAND_SLUG` and `GP_CHILD_BRAND`
- loads most theme modules from `inc/`
- includes legacy or project-specific shortcodes from `shortcodes/`
- enqueues shared front-end libraries and project scripts

### Folder Structure

```text
generatepress-child/
├── functions.php
├── style.css
├── 404.php
├── single.php
├── screenshot.png
├── README.md
├── docs/
├── inc/
├── assets/
├── JS/
└── shortcodes/
```

### Key Directories

- `inc/`: main functional layer of the theme
- `assets/css/`: shared design tokens, layout, components, search, modal, pattern, and feature CSS
- `assets/js/`: shared front-end and editor-side scripts
- `shortcodes/`: existing project-specific shortcode implementations carried forward into the theme
- `docs/`: internal project documentation for deployment notes, content model notes, and client-specific conventions

## Technical Function Reference

This section summarizes the main public or integration-facing functions used across the theme. It is not intended to list every tiny helper, but it does cover the important entry points and runtime functions by module.

### `inc/search.php`

- `gp_child_get_search_settings()`: reads the saved search configuration from the admin settings
- `gp_child_get_search_results_page_url()`: resolves the configured search results page URL, falling back safely when unset
- `gp_child_render_search_shortcode(array $atts)`: renders the `[gp_search_bar]` shortcode, including the standard and `icon` variants
- `gp_child_handle_search()`: processes AJAX live-search requests and returns result markup

### `inc/search_result/index.php`

- `signifi_search_post_types()`: defines the post types included in the results page search
- `gp_child_search_type_label(string $post_type)`: returns the display label for a post type badge
- `gp_child_render_search_result_card(WP_Post $post)`: renders a single search result card
- `custom_post_type_search_shortcode($atts)`: renders the `[post_search_result]` shortcode and full results-page layout

### `inc/search_result/ajax.php`

- `load_more_search_results()`: handles AJAX pagination for the results page "View more" behavior

### `inc/modal.php`

- `gp_modal_trigger(string $modal_id, string $label, array $args = [])`: renders a modal trigger element linked to a theme modal instance

### `inc/admin-settings.php`

- `gp_child_admin_js()`: returns inline admin JavaScript used by the settings screen
- `gp_child_sanitize_general(mixed $input)`: sanitizes site info and general settings values
- `gp_child_sanitize_options(mixed $input)`: sanitizes the operational options tab values
- `gp_child_sanitize_ga(mixed $input)`: sanitizes analytics settings such as GA4 and GTM IDs
- `gp_child_sanitize_404(mixed $input)`: sanitizes the custom 404 page selection
- `gp_child_sanitize_search(mixed $input)`: sanitizes search behavior settings
- `gp_child_sanitize_email_redirect(mixed $input)`: sanitizes mail redirection settings
- `gp_child_render_tab_help(string $text)`: renders contextual helper text inside settings tabs
- `gp_child_default_options()`: provides default settings values for the admin framework
- `gp_child_render_settings_page()`: renders the Blue Flamingo Settings admin page

### `inc/helpers.php`

- `gp_child_asset_version(string $abs_path)`: computes a stable asset version string for CSS and JS cache busting
- `gp_child_get_site_info_settings()`: reads environment-related URLs from the Site Info tab
- `gp_child_get_environment()`: returns the current environment label based on configured site URLs
- `gp_child_is_staging_environment()`: convenience helper for staging detection
- `gp_child_is_live_environment()`: convenience helper for live-site detection
- `allow_svg_upload($mimes)`: extends upload mime types to allow SVG uploads

### `inc/email-redirect.php`

- `gp_child_get_email_redirect_settings()`: reads email redirect settings from the admin panel
- `gp_child_override_mail_recipient(array $args)`: rewrites outgoing mail recipients when the redirect feature is active

### `inc/options-runtime.php`

- `gp_child_get_options_settings()`: reads the Options tab settings
- `gp_child_get_support_email()`: returns the support email used by permission and operational checks
- `gp_child_is_support_user(?WP_User $user = null)`: determines whether a user should bypass support-only restrictions

### `inc/cleanup.php`

- `gp_child_cleanup_options()`: returns the available cleanup option keys
- `gp_child_cleanup_is_enabled(string $key)`: resolves whether a specific cleanup toggle is active

### `inc/layout-defaults.php`

- `gp_child_layout_defaults_options()`: defines the supported GeneratePress layout-default options
- `gp_child_layout_default_value(string $key)`: returns the configured default value for a layout key
- `gp_child_layout_default_enabled(string $key)`: returns whether a layout default is enabled
- `gp_child_layout_default_applies_to_post(int $post_id)`: checks whether layout defaults should affect a given post

### `inc/updater.php`

- `gp_child_normalize_update_manifest(array $manifest)`: normalizes raw manifest data into the shape expected by the updater
- `gp_child_get_update_uri()`: returns the theme update URI
- `gp_child_get_update_manifest()`: fetches and caches the remote update manifest
- `gp_child_get_local_update_manifest()`: reads the local manifest from `downloads/theme.json`
- `gp_child_get_current_release_notes()`: returns current release notes from the manifest

### `inc/analytics.php`

- `gp_child_output_analytics()`: outputs the configured analytics scripts on the front end
- `gtag()`: compatibility wrapper that prints the GA dataLayer bootstrap when needed

### `inc/footer.php`

- `gp_child_render_custom_footer()`: renders the custom footer output used by the child theme

### `inc/rest-api.php`

- `gp_child_json_basic_auth_handler(mixed $user)`: handles JSON Basic Auth authentication for REST requests
- `gp_child_json_basic_auth_error(mixed $error)`: returns structured auth errors for failed REST authentication

### `inc/duplicate-content.php`

- `gp_child_duplicate_content_enabled()`: checks whether duplicate post/page functionality is enabled
- `gp_child_get_duplicate_link(int $post_id)`: builds the duplicate action URL for a post

### `inc/notes.php`

- `gp_child_get_notes()`: loads internal notes from the stored options
- `gp_child_save_notes(array $notes)`: persists internal team notes back to storage

### `inc/404.php`

- `gp_child_custom_404_template(string $template)`: swaps the default 404 template for the configured page-based template

### `inc/pwa.php`

- `gp_child_default_pwa_settings()`: defines default Progressive Web App settings
- `gp_child_get_pwa_settings()`: loads stored PWA settings
- `gp_child_is_pwa_enabled()`: returns whether PWA output is enabled
- `gp_child_sanitize_pwa(mixed $input)`: sanitizes PWA settings saved from the admin UI
- `gp_child_get_pwa_icon_url(?int $icon_id = null, string $size = 'full')`: resolves the configured app icon URL
- `gp_child_get_pwa_manifest_icons()`: builds the icon list for the manifest
- `gp_child_get_pwa_manifest_data()`: assembles the manifest payload
- `gp_child_get_pwa_scope_path()`: resolves the site scope path for the PWA
- `gp_child_get_pwa_admin_settings_js()`: returns inline admin JavaScript for the PWA settings panel
- `gp_child_get_pwa_admin_settings_css()`: returns inline admin CSS for the PWA settings panel
- `gp_child_render_pwa_settings_panel()`: renders the PWA admin settings UI
- `gp_child_get_pwa_sw_script()`: returns the service worker script content

### `inc/video-hero-block.php`

- `gp_render_video_hero_block(array $attrs, string $content)`: renders the dynamic video hero block on the front end

### `inc/enqueue_patterns.php`

- `gp_child_content_uses_pattern_class(string $content, string $class, array &$visited_refs = [])`: scans nested content for pattern classes
- `gp_child_get_pattern_scan_contents()`: collects content sources that should be scanned for pattern usage
- `signifi_page_uses_pattern(string $class)`: answers whether the current page uses a given pattern class

### `inc/webp-converter.php`

- `mbwpc_admin_notices()`: renders admin notices for the WebP conversion settings
- `mbwpc_register_settings()`: registers the WebP converter settings
- `mbwpc_image_settings_callback()`: renders the WebP settings fields
- `mbwpc_handle_upload_convert_to_webp($upload)`: converts uploaded images to WebP when enabled

## Theme Update Flow

This theme now includes a simple updater that reads a public manifest file from:

- `downloads/theme.json`

With this, you already have a working v1 manual release flow:

1. Update the version in `style.css`
2. Run `./scripts/build-theme-package.sh`
3. Review or adjust the release notes in `downloads/theme.json` if needed
4. Push the repo

The build script creates the versioned ZIP, refreshes `downloads/theme.json`, and copies both files into `~/Documents/work/BlueFlamingo/generatepress-child-versioning` when that repo exists locally. WordPress then reads the manifest and shows the update in the dashboard without FTP.

## Development Notes

- Several settings intentionally preserve the same option keys used by the old `bf-fireball` plugin
- Environment detection is shared across multiple modules
- Some legacy plugin features are still intentionally deferred because they are higher risk:
  - custom admin URL
  - debug runtime controls
  - image sharpening

## Requirements

- WordPress 6+
- GeneratePress Theme

## License

Internal project for Blue Flamingo Solutions.
