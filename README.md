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
