# GG Universal SEO

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue?logo=wordpress&logoColor=white)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-8892BF?logo=php&logoColor=white)
![WordPress 5.6+](https://img.shields.io/badge/WordPress-5.6%2B-21759B?logo=wordpress&logoColor=white)
![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-Compatible-96588A?logo=woocommerce&logoColor=white)
![License GPL-2.0+](https://img.shields.io/badge/License-GPL--2.0%2B-green?logo=gnu&logoColor=white)
![Yoast SEO Compatible](https://img.shields.io/badge/Yoast%20SEO-Compatible-A4286A)
![RankMath Compatible](https://img.shields.io/badge/RankMath-Compatible-E44D26)
![AIOSEO Compatible](https://img.shields.io/badge/AIOSEO-Compatible-005AE0)
![TranslatePress Free Compatible](https://img.shields.io/badge/TranslatePress-Free%20Compatible-1E9E4A)
![Version 1.1.0](https://img.shields.io/badge/Version-1.1.0-orange)

---

GG Universal SEO is a lightweight WordPress plugin designed for **TranslatePress (free versions)** that lets you **manually set SEO Titles, Meta Descriptions, Open Graph, Twitter, and Canonical URLs per language**. It integrates with your active SEO plugin (Yoast, RankMath, or AIOSEO) and falls back to native WordPress output if no SEO plugin is active.

---

## Why Use This Plugin?

If your site uses TranslatePress Free to serve multiple languages, your SEO metadata often stays in the default language. GG Universal SEO provides a fast, editor-friendly way to **override SEO meta for each locale** without needing a premium translation add-on or custom templates.

---

## Installation

1. Go to the [**Releases**](../../releases) section of this repository.
2. Download the latest **`.zip`** file (e.g. `gg-universal-seo.zip`).
3. In your WordPress admin panel, navigate to **Plugins → Add New → Upload Plugin**.
4. Click **Choose File**, select the `.zip` file you downloaded, then click **Install Now**.
5. Once installed, click **Activate**.

---

## Full Feature Overview

### 1) Locale Management (Settings Page)

Go to **Settings → GG Universal SEO** to define the locales you want to manage.

For each locale, enter:

| Field | Example |
|---|---|
| **Locale Code** | `en_US`, `it_IT`, `fr_FR`, `de_DE` |
| **Label** | English, Italiano, Francais, Deutsch |

**How it works**

- Locales are stored in the `gg_seo_supported_locales` option.
- The plugin validates locale codes to allow only letters, numbers, `_` and `-`.
- If no locale is configured, a default `en_US` entry is created on activation.

### 2) TranslatePress Free Integration (One-click Import)

If TranslatePress Free is active, the settings page shows an **Import Languages from TranslatePress** button.

**How it works**

- The plugin reads TranslatePress published languages and their labels.
- Imported locales are merged with existing ones (no duplicates).
- This is an admin-only action secured by a nonce and capability check.

### 3) Per-Post Locale Overrides (Meta Box)

On **Pages**, **Posts**, and **WooCommerce Products**, you will see the meta box **"GG Universal SEO — Locale Overrides"**.

For each configured locale, you can enter:

- **SEO Title**
- **Meta Description**
- **OG Title** (falls back to SEO Title if empty)
- **OG Description** (falls back to Meta Description if empty)
- **OG Image URL**
- **Canonical URL** (optional override)

**How it works**

- Each field is stored as post meta with keys like `_gg_seo_en_US_title`.
- Values are sanitized on save (text fields and URL fields separately).
- If a field is empty, its meta key is removed so defaults can flow through.

### 4) Locale Detection (TranslatePress First)

For each frontend request, the plugin determines the current locale in this order:

1. TranslatePress (global `$TRP_LANGUAGE` or TranslatePress components)
2. Polylang (`pll_current_language`)
3. WPML (`ICL_LANGUAGE_CODE` with `wpml_locale` mapping)
4. WordPress default (`get_locale()`)

Only locales listed in your settings are used for overrides.

### 5) SEO Plugin Compatibility (Adapter Layer)

GG Universal SEO detects your active SEO plugin and attaches to its filters:

| SEO Plugin | What gets overridden |
|---|---|
| **Yoast SEO** | Title, description, canonical, OG title/desc/image, locale |
| **RankMath** | Title, description, canonical, OG title/desc, og:locale |
| **AIOSEO** | Title, description, OG, Twitter, canonical, og:locale, schema |
| **None** | WordPress title + injected meta/OG/canonical tags |

**How it works**

- The plugin registers the correct filters after the queried object is available.
- AIOSEO overrides run at very high priority to ensure final output wins.
- Schema output is updated for language, title, and description when possible.

### 6) Output Buffer Fallback (Guaranteed Override)

Some SEO plugins bypass filters in multilingual contexts. To guarantee your overrides, the plugin includes a **buffer-based rewrite layer**.

**How it works**

- The plugin starts an output buffer on `template_redirect` (frontend only).
- When HTML is flushed, it rewrites:
	- `<title>`
	- `<meta name="description">`
	- Open Graph tags (title, description, image, locale, url)
	- Twitter tags (title, description, image)
	- `<link rel="canonical">`
	- `<html lang="...">`
- This runs only if there is at least one override value for the current locale.

### 7) Standalone Mode (No SEO Plugin)

If no SEO plugin is detected, GG Universal SEO still works:

- It filters the WordPress document title.
- It injects meta description, OG tags, and canonical URL into `<head>`.

---

## Supported Post Types

- Pages
- Posts
- WooCommerce Products (if WooCommerce is active)

---

## Uninstall / Cleanup

When you delete the plugin via WordPress admin, all data is removed:

- The saved locale configuration (`gg_seo_supported_locales`).
- All per-post meta keys starting with `_gg_seo_`.

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 5.6 |
| PHP | 7.4 |
| TranslatePress | Free versions supported |
| WooCommerce | Optional (for Product support) |

---

## License

This plugin is licensed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).
