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

## Theme verification (dark and light storefront)

Every probe takes the base URL as its first argument. Set `EXPECT_THEME=dark` or `EXPECT_THEME=light`
and the probe fails when `<html data-theme>` on a storefront page differs (admin pages must carry no
`data-theme` at all; `tour.mjs` checks that on every `/admin` path). Light output files carry a
`--light` suffix, so dark and light sets live side by side in one folder. The suffix follows
`EXPECT_THEME`, or the theme the page reports when it is unset.

Two local servers, one per theme (the override only works when config env is `development`):

    cd site && SKYFR_THEME=light php -S 127.0.0.1:8169 dev/router.php
    cd site && SKYFR_THEME=dark  php -S 127.0.0.1:8170 dev/router.php

`theme-switch.php` sets the real `site_theme` setting in the local DB and deletes
`storage/cache/settings.json`, so a server started without `SKYFR_THEME` shows the new theme on its
next response. It refuses to run unless config env is `development`, the DB host is local and the
database name ends in `_dev`, `_test` or `_local`. It upserts the row with group `appearance`.

    php dev-tools/theme-switch.php light
    php dev-tools/theme-switch.php dark
    php dev-tools/theme-switch.php status

`tour.mjs` (see above) also takes `EXPECT_THEME` (alias `TOUR_THEME`), prints the theme per shot,
writes `report--light.json` in light, and records the page rectangles of every visible element that
matches `TOUR_MASKS` (a CSS selector list) into the report. Exit code 1 on a theme mismatch.

    EXPECT_THEME=light node dev-tools/tour.mjs http://127.0.0.1:8169 shots/light / /shop /product/azure-oud

`theme-diff.mjs` is acceptance criterion 1. It re-captures every route in the baseline's
`report.json` with `tour.mjs` (same viewports, same scroll and animation settings), masks the hero
plates, ribbon canvas and glow, the intro, veil, cursor, spray, the WhatsApp FAB, every canvas and
video (each rect padded by `MASK_PAD`, default 6 px; add selectors with `MASK_EXTRA`), and counts the
pixels outside the masks that differ by more than `TOLERANCE` (default 2) on any channel. Diff images
(red = differing, grey = masked) land in `<out>/diff/`. It then captures computed `color`,
`background-color`, border colours, `box-shadow`, `filter` and `background-image` for every element
and pseudo-element on home, PDP, shop and checkout (desktop 1440 reduced-motion and mobile 375), and
compares them against a pre-change capture: `STYLE_REF=<json>` (a file written earlier by this tool)
or `STYLE_REF_BASE=<url>` (a server running the pre-change tree). `ONLY=/,/shop` narrows the routes,
`SKIP_PIXELS=1` / `SKIP_STYLES=1` skip a half. Exit code 1 on any difference.

Fixed elements such as the WhatsApp FAB can still be sliding when the full-page shot is taken, so
`tour.mjs` measures the mask rects both just before and just after the screenshot and `theme-diff`
masks the union. Fixed rects are padded by `MASK_PAD_FIXED` (default 32 px) sideways and by their own
height plus `MASK_PAD_FIXED` vertically, so a FAB caught mid-slide at the fold stays inside the mask.

    STYLE_REF=styles-ref.json node dev-tools/theme-diff.mjs http://127.0.0.1:8170 <scratchpad>/theme/baseline-dark shots/theme-diff

To make the style reference, serve the pre-change commit from a scratch folder
(`git archive b228662 site | tar -x -C <dir>`, copy `site/config.php` and `storage/.installed`,
symlink `uploads/`) and run the tool against it with `STYLE_OUT=styles-ref.json`.

`a11y.mjs` takes the base URL as its first argument (or `BASE`) and the JSON path as its second (or
`OUT`). With `EXPECT_THEME` it fails pages whose `data-theme` differs. Text whose box overlaps an
`<img>`, `<picture>`, `<canvas>` or `<video>` with no opaque background of its own in between is not
scored; it is listed as `REVIEW [text over media]` and under `manualReview` in the JSON. Every page's
tab order is saved under `tabOrders`; `TAB_ORDER_REF=<dark run json>` fails any page whose tab order
differs from that run. State classes (`is-*`, `has-*`, such as the hero cue's `is-hidden` after the
first scroll) are left out of each stop's signature and stripped from the reference before comparing,
so a stop only differs when its element, id, stable classes or name differ.

    EXPECT_THEME=dark  node dev-tools/a11y.mjs http://127.0.0.1:8170 shots/a11y-dark.json
    EXPECT_THEME=light TAB_ORDER_REF=shots/a11y-dark.json node dev-tools/a11y.mjs http://127.0.0.1:8169 shots/a11y-light.json

`motion-check.mjs <base> [json]` asserts `data-theme` on every page and on the intro run when
`EXPECT_THEME` is set, and prints the theme it saw.

`intro-check.mjs <base> [out]` shoots the intro at 150-2300 ms (reduced 1280, phone 360, desktop
1440) and a header link transition (home to /shop) at 40-340 ms on desktop, with the mean and median
luminance of each frame and the Skip link's contrast. `ONLY=transition-1440` (or any of
`calm-reduced-motion`, `full-360`, `full-1440`, comma separated) runs a subset. With
`EXPECT_THEME=light` it fails a displayed intro frame whose median luminance is under 0.7 and a Skip
link under 4.5:1. For the transition it reads `--sf-mist` and the `.sf-veil` background before the
click and fails if either ends in a ground at or below 0.8 luminance. Each transition frame is then
measured outside product media: every visible `img`, `picture`, `video` and `url()` background that is
not part of the hero, intro, veil or header, on the source page, the destination page and the frame
itself, is masked out. Light /shop sits near 0.69 on the whole frame because of the dark product
photography but near 0.81 outside it. A frame fails at 0.8 or less outside media, unless the pages at
rest are themselves at or below 0.8 there (home in light is about 0.79 with the dark ribbon strokes),
in which case it fails only when it is darker than the darker page at rest minus 0.03.

`hero-gl-probe.mjs <base> [out]` runs the WebGL hero under SwiftShader. After the timed shots it hides
the hero copy and header, shoots the ribbon canvas, hides the canvases, shoots the ground behind them,
and compares the two pixel by pixel: ribbon pixels are those that differ by more than 12 on a
channel, pale holes are ribbon pixels lighter than the ground. It fails when the canvas is missing,
when under 0.5% of it shows ribbon, and in light when more than 5% of ribbon pixels are pale or the
ribbons are not darker than the ground on average. It records every `sf:motion:hero` event with its
state, reason, time and the scene's last mean frame time (`SF.motion.hero`). If the scene fell back
before or during sampling it fails with the reason, for example `GL fell back: budget at 30.94 ms mean
frame time (t=10546 ms)`, and discards the sample instead of reporting pale holes from a canvas being
torn down; the sample is only taken while the canvas exists and the last event is `gl`. It also logs
the motion images requested: light
must request `ribbons-*-light-*.webp` and no dark plate, dark must request no light plate.
`hero-mobile-probe.mjs <base> [out]` does the same network and theme checks on a Pixel 5.

    EXPECT_THEME=light node dev-tools/hero-gl-probe.mjs http://127.0.0.1:8169 shots/hero-gl

`lighthouse-theme.mjs <reference-base> <candidate-base> [out]` is criterion 7: mobile Lighthouse
performance (simulated throttling) on `/`, `/shop` and `/product/azure-oud`, `RUNS` (default 3) runs
per page per server, interleaved, compared by median. It fails when the candidate is more than
`ALLOWANCE` (default 2) points below the reference. `EXPECT_THEME_A` / `EXPECT_THEME_B` assert the
theme each server serves; `PAGES` overrides the list. Uses the Lighthouse install in
`dev-tools/node_modules` and Playwright's Chromium.

    EXPECT_THEME_A=dark EXPECT_THEME_B=light LABEL_A=dark LABEL_B=light node dev-tools/lighthouse-theme.mjs http://127.0.0.1:8170 http://127.0.0.1:8169 shots/lighthouse
