# dev-tools (never shipped)

`tour.mjs` — full-page screenshots at 1440px and 375px for a list of paths, scrolling first so
scroll-reveal content is captured, and a `report.json` with HTTP status, horizontal-overflow flag
and page/console/network errors per shot.

    node dev-tools/tour.mjs http://127.0.0.1:8088 shots/stage2 / /shop /product/azure-oud /cart

Admin pages: `TOUR_COOKIE=skyfr_admin=<session id> node dev-tools/tour.mjs ... /admin /admin/orders`

Uses the Playwright install in ../rangeaahan/node_modules (Chromium 1208 cached in
~/Library/Caches/ms-playwright). Local servers: `php -S 127.0.0.1:8088 dev/router.php` from site/.
