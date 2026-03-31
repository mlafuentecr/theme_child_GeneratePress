# GeneratePress Child Theme — Blue Flamingo

Child theme built on GeneratePress. Most custom logic lives in `inc/` and is loaded from `functions.php`.

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

Shortcodes:

```text
[gp_search_bar]
[post_search_result]
```

Modes:

- `Live search dropdown`: AJAX results below the input
- `Redirect to results page`: sends visitors to a selected page that contains `[post_search_result]`

Recommended setup:

1. Create a page such as `Search`
2. Add `[post_search_result]` to that page
3. Select that page in the Search tab
4. Use `[gp_search_bar]` anywhere you want the search field

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
- `inc/layout-defaults.php`: GeneratePress layout defaults
- `inc/duplicate-content.php`: duplicate posts/pages
- `inc/notes.php`: internal notes and dashboard notices
- `inc/rest-api.php`: JSON Basic Auth runtime

## Development Notes

- Several settings intentionally preserve the same option keys used by the old `bf-fireball` plugin
- Environment detection is shared across multiple modules
- Some legacy plugin features are still intentionally deferred because they are higher risk:
  - custom admin URL
  - debug runtime controls
  - image sharpening

See also:

- `docs/blue-flamingo-options-parity.md`

## Requirements

- WordPress 6+
- GeneratePress Theme

## License

Internal project for Blue Flamingo Solutions.
