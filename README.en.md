# wordpress-original

[Español](README.md) · **English**

WordPress site for **Nortia Reformas**, a fictional home-renovation company in Gijón (Spain). It mimics the typical website of a Spanish small business and is the **starting point for the proof of concept of migrating to Astro + headless WordPress**.

## Requirements

- Docker Desktop (or Docker Engine + Compose v2)
- Internet access on first run (downloads WordPress, the theme, plugins, translations and sample photos)

## Start

```bash
docker compose up -d
docker compose run --rm wpcli sh /setup/setup.sh
```

Installation takes a few minutes (mostly because of ~35 images).

- Site: http://localhost:8080
- Admin: http://localhost:8080/wp-admin → `admin` / `admin`
- Blog editor: `marta` / `marta`

To use another port: `WP_PORT=8090 docker compose up -d` (and the same variable when running the setup).

## Reset from scratch

```bash
docker compose down -v
docker compose up -d
docker compose run --rm wpcli sh /setup/setup.sh
```

## Useful commands

```bash
docker compose run --rm wpcli wp plugin list
docker compose run --rm wpcli wp post list --post_type=proyecto
docker compose run --rm wpcli wp db export /var/www/html/backup.sql
docker compose run --rm wpcli sh /setup/diagnostico.sh   # response time with each plugin enabled
```

## What's included

### Theme and plugins (the usual ones for a small business)

| Item | Purpose |
|---|---|
| Astra | Theme widely used on corporate sites |
| Advanced Custom Fields | Custom fields (projects, team, services) |
| Yoast SEO | Titles and meta descriptions (already filled in on pages, posts and projects) |
| Contact Form 7 + Flamingo | Quote request form and message log |
| Cookie Notice | Cookie banner |
| Redirection | Redirects |
| UpdraftPlus | Backups |
| Wordfence | Security (installed but **deactivated**: locally it adds about 16 s per request) |
| WP Mail SMTP | Email delivery |
| Smush | Image optimisation |
| Yoast Duplicate Post | Duplicate content |

### Custom code (`mu-plugins/nortia-core.php`)

Mimics what the agency that built the site would typically code:

- **Projects** post type (`/proyectos/`) with a **Project type** taxonomy.
- **Team** post type (not public, shown through a shortcode).
- ACF field groups: project details, team member details and service details (price, timeframe and what's included) on child pages.
- Shortcodes `[nortia_proyectos]` and `[nortia_equipo]`.
- A `the_content` filter that automatically adds the project spec sheet and before/after photos to each project, and the price box to each service.

### Content (`setup/content.php`)

- **Pages:** Home, About us, Services (with 4 sub-pages), Blog, Contact, Legal notice, Privacy policy and Cookie policy.
- **Blog:** 8 posts in 4 categories, with tags, featured image, author (editor) and comments with replies.
- **Projects:** 6, with ACF fields, before/after images and 3 featured on the home page.
- **Team:** 4 people.
- Main menu with sub-menu, legal menu, logo, site icon, static front page and `/%postname%/` permalinks.
- A variety of Gutenberg blocks: cover, columns, buttons, images, lists, quotes, group, latest posts, shortcode and HTML (OpenStreetMap map).

Photos are downloaded from picsum.photos. Without network access, placeholder images are generated.

## What to measure before migrating

1. PageSpeed Insights / Lighthouse (mobile) of: home, one service, one project and one blog post.
2. Total page weight and number of requests (DevTools → Network).
3. Server response time (TTFB).
4. Number of plugins, scripts and stylesheets loaded.

For a fair comparison, also measure with a caching plugin enabled (e.g. WP Super Cache), as a real client would have.

## Cases the migration must cover

- Page hierarchy and URLs (`/servicios/reforma-de-banos/`, `/proyectos/...`, `/category/...`, `/tipo-proyecto/...`).
- Yoast SEO metadata.
- ACF fields and the logic of the `the_content` filter and the shortcodes.
- Contact Form 7 form.
- Nested menus, comments, authors, categories and tags.
- Dynamic "Latest posts" block and HTML block with an iframe.

## To do (scenario 2)

A variant with the home page built in **Elementor**, to measure the most common "messy" case on real client sites.
