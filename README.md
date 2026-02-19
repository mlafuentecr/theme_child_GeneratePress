# GeneratePress Child Theme

Child theme designed to extend **GeneratePress** with safe, maintainable customizations.  
Ideal for adding styles, functions, custom post types, and templates **without losing changes when the parent theme updates**.

---

## ⚠️ ATTENTION – Automatic Deployment to Staging

This repository is connected to a **GitHub Actions workflow** that **automatically deploys to Kinsta Staging**.

### 🚨 Important

- **Any push to the `main` branch will trigger a deployment to STAGING**
- There is **no manual approval step**
- Use `main` **only when changes are ready to be tested on staging**

👉 If you are working on experimental or unfinished features:

- Use a feature branch
- Open a Pull Request instead of pushing directly to `main`

---

### 🔄 Deployment Workflow (Staging)

- **Workflow name:** `Deploy to Kinsta (Staging)`
- **Trigger:** `push` to `main`
- **Target:** Kinsta Staging environment
- **Method:** `rsync` over SSH
- **Cache handling:** PHP OPcache restart after deploy

````yaml
name: Deploy to Kinsta (Staging)
---

## Requirements

- WordPress **6.x** or higher
- Parent theme: **GeneratePress**
  - Recommended version: **3.6.0**
  - Author: Tom Usborne
- Local development recommended with **KinstaDev**

---

## Theme Structure

```text
generatepress-child/
│
├── style.css        # Theme header + custom styles
├── functions.php    # Theme bootstrap
│
├── assets/
│   ├── css/         # Custom CSS files
│   ├── js/          # Custom JavaScript
│   └── images/      # Theme images
│
├── inc/             # Modular PHP logic
│   ├── ajax/
│   ├── blocks/
│   ├── search_result/
│   ├── cleanup.php
│   ├── custom-post-types.php
│   ├── enqueue.php
│   ├── enqueue_blocks.php
│   ├── enqueue_patterns.php
│   ├── helpers.php
│   ├── menus.php
│   ├── patterns.php
│   ├── shortcode.php
│   └── top-bar.php
│
└── README.txt


---

## STYLE.CSS

- Declares the child theme metadata (name, author, template, version).
- Loads automatically after GeneratePress styles.
- Contains all custom CSS overrides and extensions.

No preprocessors or build tools are used.
CSS is written and maintained directly.

---

## FUNCTIONS.PHP

Acts as the central bootstrap for the child theme:

- Loads all PHP modules from the /inc/ directory.
- Registers theme features and menus.
- Enqueues styles and scripts.
- Keeps logic separated and maintainable.

---

## CUSTOM POST TYPES

Custom Post Types are registered in:

inc/custom-post-types.php

Examples:

- industries
- use_cases
- solutions

These CPTs are designed to work with:

- Gutenberg Query Loop
- Patterns
- Custom Blocks

---

## ENQUEUE STRATEGY

All styles and scripts are loaded via:

- inc/enqueue.php
- inc/enqueue_blocks.php
- inc/enqueue_patterns.php

Assets use file-based versioning (filemtime) for cache busting.

---

## LOCAL DEVELOPMENT (KINSTADEV)

1. Start KinstaDev.
2. Open the site shell.
3. Navigate to:

   wp-content/themes/generatepress-child

4. Activate the theme from the WordPress admin panel.

---

## BEST PRACTICES

- Keep business logic inside /inc/.
- Keep styles scoped and organized.
- Avoid adding logic directly inside templates.
- Do not modify GeneratePress core files.
- Commit only what is necessary to version control.

---

## NOTES

This setup is optimized for:

- WordPress + GeneratePress
- Long-term maintainability
- Clean, modular development
- Agency or client-ready projects

Maintained using KinstaDev.
````
