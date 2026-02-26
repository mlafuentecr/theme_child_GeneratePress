# GeneratePress Child Theme — Blue Flamingo

Child theme built on GeneratePress. All custom logic lives in `inc/` and is loaded from `functions.php`.
git push blueflamingo main

---

## Structure Overview

```
generatepress-child/
├── assets/
│   ├── css/
│   ├── js/
├── inc/
│   ├── setup.php
│   ├── enqueue.php
│   ├── admin-settings.php
│   ├── animations.php
│   ├── parallax.php
│   ├── modal.php
│   ├── dfi.php
│   ├── helpers.php
│   ├── patterns.php
│   ├── popup-block.php
│   ├── search-header.php
│   └── search_result/
└── functions.php
```

---

## Core Modules

### setup.php

Declares theme support (`title-tag`, `post-thumbnails`) and registers two navigation menus: **Primary** and **Footer**.

### enqueue.php

Enqueues:

- `assets/css/theme.css`
- `assets/js/theme.js`

Versioning combines `GP_CHILD_VERSION` with a manual cache-buster counter stored in the database.

---

## Admin Settings

Located under:
**Appearance → Blue Flamingo Settings**

Tabs:

- General (Live / Staging URLs)
- Analytics (GA4 / GTM)
- Default Featured Image
- Advanced (Cache Buster)

Settings use the same option keys as the `bf-fireball` plugin to preserve data.

---

## Features

### Scroll Animations

Activated via CSS classes:

- `fade-up`
- `fade-down`
- `fade-left`
- `fade-right`
- `zoom-in`
- `zoom-out`

Optional attributes:

- `data-animate-delay="200"`
- `data-animate-duration="1000"`

Respects `prefers-reduced-motion`.

---

### Parallax

Activate with:

```
<div data-parallax></div>
```

Options:

- `data-parallax-speed`
- `data-parallax-axis`
- `data-parallax-direction`

---

### Modal System

Shortcodes:

```
[gp_modal id="x" size="md"]...[/gp_modal]
[gp_modal_trigger id="x"]Open[/gp_modal_trigger]
```

Supports keyboard navigation and focus trapping.

---

### Search System

Shortcode:

```
[post_search_result]
```

Searches across Posts, Pages, Solutions, Use Cases, and Industries.
Supports AJAX load-more.

---

### Header Search

Shortcode:

```
[gp_search_bar]
```

Adds a clean inline search input anywhere.

---

## Development Notes

- All custom logic lives inside `inc/`
- No duplicated functionality when `bf-fireball` plugin is active
- SVG uploads enabled
- Comments disabled globally
- Full-width layout enforced
- Custom block pattern category registered

---

## Requirements

- WordPress 6+
- GeneratePress Theme

---

## License

Internal project for Blue Flamingo Solutions.
