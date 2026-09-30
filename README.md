# Sky Fragrances — storefront, checkout and admin panel

The online shop for Sky Fragrances (skyfragrances.com), a Pakistani perfume brand. One plain-PHP
site that runs on Hostinger shared hosting: a luxury storefront, guest checkout with cash on
delivery and manual transfers (bank / JazzCash / Easypaisa), order tracking, a Scent Finder quiz,
and a mobile-friendly admin panel at `/admin`.

No framework, no Composer, no Node, no build step. Upload the ZIP, run `install.php`, delete it.

## What is in this repository

| Path | What it is |
|---|---|
| `site/` | The website exactly as it is deployed. Its contents become Hostinger's `public_html`. |
| `site/install.php` | One-time installer: checks the server, writes `config.php`, creates the tables, the admin account and the sample data, then deletes itself. |
| `site/db/schema.sql`, `site/db/seed.sql` | Database blueprint and sample data (12 perfumes, 5 collections, 3 coupons). Runs on MySQL 8 and MariaDB 10.4+. |
| `site/app/` | Storefront code (controllers, views, libraries, PHPMailer). Blocked from the web by `.htaccess`. |
| `site/admin/` | Admin panel code. |
| `site/assets/` | Stylesheets (`critical.css` + `site.css` for the storefront, `admin.css`, `motion.css` assembled from `css/motion/*.css`), vanilla JS, the motion layer (`js/motion/**`, `js/intro.js`) with its vendored GSAP, ScrollTrigger and Lenis (`js/vendor/`), two variable fonts, brand images. |
| `site/uploads/` | Product, collection and social-preview images. The ZIP ships only the sample set. |
| `site/storage/` | Runtime files (sessions, logs, cache, payment screenshots). Empty in the ZIP. |
| `site/dev/` | Local-only helpers (`router.php` for `php -S`, the ZIP manifest). Never shipped. |
| `dev-tools/` | `build-zip.php` (makes the deployable ZIPs), `build-update-pack.sh` (code-only update pack), `tour.mjs` (screenshots), `motion-check.mjs`, `intro-check.mjs`, `gzip-proxy.mjs`, `extract-default-copy.php`. Never shipped. |
| `dist/` | Build output: the dated build `skyfragrances-YYYYMMDD-HHMM.zip` (the time is UTC), its two stable copies `skyfragrances-public_html-folder.zip` (wrapped in `public_html/`, the one the owner uploads) and `skyfragrances-public_html.zip` (flat), the code-only `skyfragrances-update-YYYYMMDD-HHMM.zip`, and the unpacked twin `dist/public_html/`; superseded builds sit in `dist/old-builds/`. Git-ignored. |
| `docs/` | Plans, specs, review reports and the owner-facing guides (see below). |
| `brand/` | Logo sources. |

## Documents for the owner

- `docs/GO-LIVE-GUIDE.md` — step-by-step Hostinger launch for a non-developer: database, mailbox,
  upload, installer, HTTPS, first hour in the panel, test orders, troubleshooting, updates, backups.
- `docs/HANDOVER.md` — every credential to set, every placeholder to replace, where each setting
  lives, known limitations, the motion layer, and the fresh-install rehearsals.

## Documents for a developer

- `docs/plan/00-brief.md` — the client brief, verbatim. Source of truth for scope.
- `docs/plan/08-decisions-register.md` — the referee between the fourteen spec units. It overrides
  every other spec when they disagree.
- `docs/plan/` — the numbered specs (schema, architecture, storefront, design, admin, commerce, seed
  and quality bar).
- `docs/launch/` — the pre-launch smoke reports and screenshot tours for the storefront and admin,
  plus `shots-fresh-install/` and `shots-fresh-install-zip/` (screenshots and `evidence.json` from
  installing the ZIP on a throwaway database, as recorded in `docs/HANDOVER.md` §7 and §7b).
- `docs/acceptance-report.md` — the 07 B.4 acceptance run, end to end.
- `docs/motion/` — the motion layer: brief, plan, per-section notes, `run-report.md` with its
  measured numbers and the kill-switch order in `PLAN.md`. `docs/lighthouse/summary.md` and
  `docs/perf/critical-css.md` hold the perf pass.
- `docs/dev-run.md` — how the local servers and screenshot tooling are run.

## Branches

| Branch | State |
|---|---|
| `main` | The launch build: storefront, commerce engine, intro loader, the signature motion layer (merged in `0ff6deb`), admin panel. Build the ZIP from here. |
| `motion-wip` | The branch the motion layer was finished on. Merged into `main`; kept for history only. |

## Building the ZIP

```bash
php dev-tools/build-zip.php
```

Writes `dist/skyfragrances-YYYYMMDD-HHMM.zip` from `site/` using the rules in
`site/dev/zip-manifest.php`, then copies it to the two stable names `dist/skyfragrances-public_html.zip`
(flat root) and `dist/skyfragrances-public_html-folder.zip` (everything under `public_html/`, the
file the owner uploads — hPanel's extractor keeps dot-files only inside a folder). Rules: no
`config.php`, no runtime state, no `dev/`, no `.git`, no `.DS_Store`, no owner uploads. Under
`uploads/` only the sample set ships: `products/sample/**`, `og/sample/**` and the five seed
collections' art in `collections/` — the build reads the image names out of `db/seed.sql`, derives
the `-thumb/-card/-zoom` and `-og` files, refuses to finish if any is missing, and refuses if any
other upload slipped in. The sample images, the motion layer (`motion.css`, `js/motion/*`, the
vendored GSAP/ScrollTrigger/Lenis), the intro loader, PHPMailer, both SQL files and `install.php`
are required. Last build: 595 files, 13,928,115 bytes flat / 13,943,403 bytes wrapped.

```bash
zsh dev-tools/build-update-pack.sh
```

Writes `dist/skyfragrances-update-YYYYMMDD-HHMM.zip`: `site/` minus `install.php`, `config*.php`,
`dev/`, `storage/` and `uploads/`, plus an `UPDATE-README.txt`, for extracting inside a live
`public_html` (guide, Part 11).

To inspect what the owner will upload:

```bash
rm -rf dist/public_html && mkdir -p dist/public_html && unzip -q dist/skyfragrances-public_html.zip -d dist/public_html
```

## Running locally

Requirements: PHP 8.2 with `pdo_mysql`, `gd`, `mbstring`, `intl`, `fileinfo`, `curl`, `zip`; MySQL 8
or MariaDB 10.4+.

```bash
cd site
php -S 127.0.0.1:8088 dev/router.php
```

Open `http://127.0.0.1:8088/install.php` and follow the four screens (create an empty database
first). On a local address the installer keeps `install.php` in place and tells you to delete it;
on the real domain it deletes itself.

To test the SQL against MariaDB directly:

```bash
mariadb -u<user> -p<pass> <db> < site/db/schema.sql
mariadb -u<user> -p<pass> <db> < site/db/seed.sql
```

## Conventions

- Plain PHP 8.2 + PDO with prepared statements everywhere; every output escaped with `e()`; CSRF
  token on every state change; every included file starts with the `SKYFR` guard.
- Money passes through `money()` / `money_paisa()`; time through `now_karachi()`.
- No comments or docblocks in code files. Explanations live in commit messages and in `docs/`.
- One stylesheet per surface, vanilla JS only, nothing that needs a build step.
- Changing an API response or a setting key means checking every consumer first: storefront,
  admin, emails.

## Tests that exist

There is no unit-test suite. Verification is done by lint (`php -l`, `node --check`), the smoke
runs in `docs/launch/`, the acceptance run (`docs/acceptance-report.md`), the motion harness
(`dev-tools/motion-check.mjs`, `docs/motion/run-report.md`), the screenshot tour
(`dev-tools/tour.mjs`) and a fresh install from the ZIP on a throwaway database with a headless
browser walking `install.php`, the checkout and the admin, as recorded in `docs/HANDOVER.md` §7b.
