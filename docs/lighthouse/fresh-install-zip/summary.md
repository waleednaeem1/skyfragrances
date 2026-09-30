# Lighthouse — the 2026-09-29 ZIP as installed (motion layer on)

Run 2026-09-29 11:58 Karachi against the fresh-install rehearsal in `docs/HANDOVER.md` §7b:
`dist/skyfragrances-public_html.zip` unzipped into a scratch folder, installed through
`install.php` on a new MySQL 9.3 database, served by `php -S 127.0.0.1:8101` with the dev router
outside the tree. Lighthouse 13.5.0 (`dev-tools/node_modules`), Chrome for Testing 145,
`--preset=perf --form-factor=mobile --screenEmulation.mobile --throttling-method=simulate`,
one run per page. The gzip rows go through `dev-tools/gzip-proxy.mjs` on `:8112`, which
compresses the HTML the way Hostinger's `.htaccess` `mod_deflate` block does; `php -S` cannot.
Raw JSON was not kept (git-ignored, ~400 KB each).

| Page | Server | Performance | LCP | FCP | TBT | CLS | Transfer |
|---|---|---|---|---|---|---|---|
| `/` | gzip (production-equivalent) | **98** | 2.3 s | 1.1 s | 0 ms | 0 | 409 KiB |
| `/shop` | gzip | **98** | 2.3 s | 1.1 s | 0 ms | 0.001 | 432 KiB |
| `/product/azure-oud` | gzip | **98** | 2.5 s | 1.1 s | 0 ms | 0 | 352 KiB |
| `/` | raw `php -S` (HTML uncompressed) | 95 | 2.9 s | 1.7 s | 0 ms | 0 | 471 KiB |

Every page is above the 85 bar in `docs/motion/PLAN.md` §6.4, so no kill switch was entered.
The numbers match the perf pass in `../summary.md` (98 / 98 / 96–98) within run-to-run noise.
