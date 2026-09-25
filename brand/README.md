# Sky Fragrances — brand assets

`logo-original.jpg` is the file supplied by the owner on 2026-09-25 (1254x1254 JPEG, black
background baked in, no transparency; it arrived named `logo.png`). It is the master for every
asset on a dark background. Everything in `derived/` is generated from it with ImageMagick.

| File | Use |
|---|---|
| `derived/lockup-on-black-800.webp` | Header/footer/hero on the black site background, emails on dark |
| `derived/lockup-transparent.png` (763x865) | Full lockup for ivory or white contexts: invoices, packing slips, emails on white |
| `derived/lockup-transparent-800.png`, `-400.png` | Same, resized |
| `derived/monogram-transparent.png` (341x459) | SF monogram only, for ivory/white contexts |
| `derived/monogram-transparent-512.png` | Monogram centred in a 512 square with alpha (OG/social when a square is needed) |
| `derived/monogram-on-black-512.png` | Icon master: monogram on #0A0A0A with 30% padding |
| `derived/apple-touch-icon-180.png`, `favicon-32.png`, `favicon-16.png` | Favicon set, cut from the icon master |
| `derived/previews/` | Flattened previews on ivory #F5F0E8 for eyeballing, not for the site |

How the transparent versions were made, so they can be regenerated from a better source:
alpha = max(R,G,B) of the source, blurred 0.3px and `-level 7%,100%` to kill JPEG noise;
then the colour layer is flattened onto gold #C9A96E and the alpha re-attached, which turns
the dark anti-aliased edge pixels (blended against black in the source) back into gold. A vector
source (SVG/AI/PDF) from the designer would make all of this unnecessary and sharper; ask for one.
