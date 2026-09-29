# dev-tools (never shipped)

`tour.mjs` — full-page screenshots at 1440px and 375px for a list of paths, scrolling first so
scroll-reveal content is captured, and a `report.json` with HTTP status, horizontal-overflow flag
and page/console/network errors per shot.

    node dev-tools/tour.mjs http://127.0.0.1:8088 shots/stage2 / /shop /product/azure-oud /cart

Admin pages: `TOUR_COOKIE=skyfr_admin=<session id> node dev-tools/tour.mjs ... /admin /admin/orders`

Uses the Playwright install in ../rangeaahan/node_modules (Chromium 1208 cached in
~/Library/Caches/ms-playwright). Local servers: `php -S 127.0.0.1:8088 dev/router.php` from site/.

`extract-default-copy.php` — reads `site/db/seed.sql` and writes `site/app/data/default-copy.php`,
the file the admin "Restore default text" / "Restore default wording" buttons read from. Run it
after every copy change in seed.sql; `build-zip.php` runs it with `--check` and refuses to build
while the data file is stale. Statements may hold one row or many (`VALUES (...), (...)`, the
mysqldump extended-insert form); every row is read and any text after the last row aborts the run.

    php dev-tools/extract-default-copy.php
    php dev-tools/extract-default-copy.php --check
