# Motion layer — vendored libraries

All motion libraries are static files under `site/assets/js/vendor/`, served same-origin
(`script-src 'self'`), cache-busted by `?v=<version>` (the `data-motion-v` attribute on the
`core.js` tag = newest mtime across `assets/js/motion/*.js` and `assets/js/vendor/*.js`), and
gzipped by Apache `mod_deflate`. No CDN, no import map, no `unsafe-eval`, no bundler. Nothing in
this folder is ever referenced by a `<script>` tag in PHP: `core.js` injects each file on demand.

| File | Package / version | Raw | gzip -9 | Licence | Loaded when |
|---|---|---|---|---|---|
| `gsap.min.js` | `gsap@3.13.0` (`dist/gsap.min.js`) | 72,435 B | 28,182 B | GSAP Standard "no charge" licence (`LICENSE-gsap.txt`, https://gsap.com/standard-license) | Desktop class only, after `load` + idle (or earlier when a `[data-motion]` root nears the viewport) |
| `ScrollTrigger.min.js` | `gsap@3.13.0` (`dist/ScrollTrigger.min.js`) | 44,157 B | 17,841 B | same | With `gsap.min.js`, always together |
| `lenis.min.js` | `lenis@1.3.26` (`dist/lenis.min.js`, IIFE, `globalThis.Lenis`) | 18,722 B | 5,431 B | MIT (`LICENSE-lenis.txt`) | Desktop class only, after GSAP, only when `flags.lenis` is on (ships `false`) and never on `body.cart/checkout/track/confirmation` |
| Three.js | not vendored | — | — | — | PLAN.md §8 #1 picks the in-house WebGL2 module `motion/ribbons-gl.js` for the hero; Three stays an opt-in alternative and would be added here as one self-contained module file if the client asks for it |

Desktop worst case with every flag on: 51,454 B gz of library on top of the page, all after the
load event. Mobile, low and reduced classes load 0 B from this folder.

Provenance: npm registry tarballs, sha1 `597d4e019a2bb487785387d91296adebf92db9dd` (gsap-3.13.0.tgz)
and `2262e4fa3de7b7085aea82a64e204b234711d24d` (lenis-1.3.26.tgz). Files copied byte-for-byte from
`package/dist/`; `node --check` passes on all three. The GSAP npm package ships no LICENSE file —
`LICENSE-gsap.txt` reproduces the file header notice and the licence URL from `package.json`.

## Updating

1. Download the new tarball from the registry, verify its sha1 against the registry's `dist.shasum`.
2. Copy `dist/gsap.min.js` + `dist/ScrollTrigger.min.js` (same GSAP version, always together) or
   `dist/lenis.min.js`; keep the file names — `core.js` loads them by name.
3. Check the global each file exposes (`window.gsap`, `window.ScrollTrigger`, `globalThis.Lenis`);
   `core.js` reads those globals and does not import the files as ES modules.
4. Update the table above (raw + `gzip -9 -c file | wc -c`), then run `node --check` on the file
   and the Playwright pass described in `motion-api.md` § Verification.

The `?v=` version changes automatically on the next request because it is derived from file mtimes.
