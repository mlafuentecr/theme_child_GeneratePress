# Blue Flamingo Options Parity

This matrix tracks the old `bf-fireball` `Options` tab against the child theme.

| Plugin option | Theme status | Theme module / UI | Notes |
| --- | --- | --- | --- |
| `activate_stripe_test_mode` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Forces WooCommerce Stripe test mode on staging environments configured in Site Info. |
| `activate_wpsimplepay_testmode` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Forces WP Simple Pay test mode on staging environments. |
| `Show_all_meta_fields` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Shows all post meta on the editor screen for debugging/content audits. |
| `disable_admin_notifications_of_password_changes` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Suppresses the default WordPress password-change admin email. |
| `hide_google_recaptcha_logo` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Existing theme feature. |
| `admin_user_registration_date` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Existing theme feature. |
| `enable_duplicate_content` | Implemented | `inc/duplicate-content.php`, `inc/admin-settings.php` | Theme-native feature replacing plugin behavior. |
| `default_featured_image` | Implemented | `inc/dfi.php`, `inc/admin-settings.php` | Runtime stays in dedicated DFI module. |
| `id_whatConverts` | Implemented | `inc/analytics.php`, `inc/admin-settings.php` | Moved out of `admin-settings.php` runtime. |
| `restrict_admin_creation` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Blocks assigning the Administrator role for non-support users. |
| `restrict_plugin_management` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Restricts plugin activation, deactivation, updates, and deletion for non-support users. |
| `limit_ability_to_add_new_plugin` | Implemented | `inc/options-runtime.php`, `inc/admin-settings.php` | Hides and blocks plugin installation UI for non-support users. |
| `auto_delete_standard_theme` | Implemented with caution | `inc/options-runtime.php`, `inc/admin-settings.php` | Deletes unused default WordPress themes; destructive option, so keep it intentional. |
| `enable_custom_admin_url` / `custom_admin_url` | Deferred | Not moved | High-risk login-routing behavior; should be ported only as a dedicated module with rollout plan. |
| `wp_debug` / `wp_debug_log` / `wp_debug_display` | Deferred | Not moved | Better handled with a dedicated debug module and environment policy. |
| `sharpen_images` | Deferred | Not moved | Image processing side effects need separate design/testing. |
