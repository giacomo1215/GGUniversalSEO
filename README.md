# GG Universal SEO

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue?logo=wordpress&logoColor=white)
![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-8892BF?logo=php&logoColor=white)
![WordPress 5.6+](https://img.shields.io/badge/WordPress-5.6%2B-21759B?logo=wordpress&logoColor=white)
![WooCommerce Compatible](https://img.shields.io/badge/WooCommerce-Compatible-96588A?logo=woocommerce&logoColor=white)
![License GPL-2.0+](https://img.shields.io/badge/License-GPL--2.0%2B-green?logo=gnu&logoColor=white)
![Yoast SEO Compatible](https://img.shields.io/badge/Yoast%20SEO-Compatible-A4286A)
![RankMath Compatible](https://img.shields.io/badge/RankMath-Compatible-E44D26)
![AIOSEO Compatible](https://img.shields.io/badge/AIOSEO-Compatible-005AE0)
![Version 1.0.0](https://img.shields.io/badge/Version-1.0.0-orange)

---

A lightweight WordPress plugin that lets you **manually set SEO Titles and Meta Descriptions for each language** on your site. It works alongside your existing SEO plugin (Yoast, RankMath, or AIOSEO) — or on its own if you don't use one.

---

## Why Use This Plugin?

If your WordPress site serves content in **multiple languages**, your SEO metadata (page title, meta description) often stays in just one language. GG Universal SEO gives you a simple editor to write locale-specific SEO data for every page, post, or product — no complex translation plugin required.

---

## Installation

1. Go to the [**Releases**](../../releases) section of this repository.
2. Download the latest **`.zip`** file (e.g. `gg-universal-seo.zip`).
3. In your WordPress admin panel, navigate to **Plugins → Add New → Upload Plugin**.
4. Click **Choose File**, select the `.zip` file you downloaded, then click **Install Now**.
5. Once installed, click **Activate**.

That's it — the plugin is ready to use.

---

## How It Works

### Step 1 — Configure Your Languages

Go to **Settings → GG Universal SEO**.

Add the languages (locales) your site supports. For each one, enter:

| Field | Example |
|---|---|
| **Locale Code** | `en_US`, `it_IT`, `fr_FR`, `de_DE` |
| **Label** | English, Italiano, Français, Deutsch |

Click **Save Locales**.

### Step 2 — Write SEO Data Per Language

Open any **Page**, **Post**, or **Product** in the editor. You'll see a new meta box called **"GG Universal SEO — Locale Overrides"**.

For each language you configured, fill in:

- **SEO Title** — The page title shown in search engine results.
- **Meta Description** — The short summary shown below the title in search results.

Save/update the post as usual.

### Step 3 — Automatic Frontend Injection

When a visitor views your site, the plugin:

1. Checks the **current locale** of the page (set by WordPress or your translation plugin).
2. Looks up whether you've written SEO metadata for that locale.
3. If a match is found, it **overrides** the title and description tags in the page's HTML.

This happens automatically — no shortcodes, no template edits.

---

## SEO Plugin Compatibility

GG Universal SEO detects your active SEO plugin and hooks into the correct filters:

| SEO Plugin | Status |
|---|---|
| **Yoast SEO** | ✅ Fully supported |
| **RankMath** | ✅ Fully supported |
| **All in One SEO (AIOSEO)** | ✅ Fully supported |
| **No SEO plugin** | ✅ Works standalone (uses native WordPress title + meta tag) |

The plugin's Settings page also shows which SEO plugin it has detected.

---

## Supported Post Types

- Pages
- Posts
- WooCommerce Products

---

## Uninstall / Cleanup

If you delete the plugin through the WordPress admin, **all data is removed automatically**:

- The saved locale configuration.
- All per-post SEO title and description values.

No leftover data in your database.

---

## Requirements

| Requirement | Minimum |
|---|---|
| WordPress | 5.6 |
| PHP | 7.4 |
| WooCommerce | Optional (for Product support) |

---

## License

This plugin is licensed under the [GPL-2.0+](https://www.gnu.org/licenses/gpl-2.0.html).
