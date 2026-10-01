# Light theme: art direction verdict

Lens: luxury feel (would Byredo, Le Labo or Aesop ship it), coherence with the dark theme as one house, typography and rhythm, how gold and the logo read on a light ground, how product imagery is framed, and how original the concept is for a brand called Sky.

Evidence: every screenshot listed for the three directions, compared against the dark baseline (`baseline-dark/home--desktop.png`, `home--mobile.png`, `product_azure_oud--mobile.png`). I cut the full-page desktop captures into 2300px tiles and put the three mobile captures side by side at the same scroll depth so each section could be compared directly. The working crops are in the scratchpad at `theme/judge-art2/`.

## Scores

| Criterion | 1 Porcelain and Gilt | 2 Dawn Sky | 3 Atelier Parchment |
|---|---|---|---|
| Luxury feel | 8 | 6 | 8 |
| Coherence with the dark theme | 7 | 8 | 9 |
| Typography and rhythm | 9 | 7 | 8 |
| Gold and logo on light | 7 | 5 | 8 |
| Product imagery framing | 9 | 6 | 8 |
| Originality for "Sky" | 5 | 9 | 6 |
| **Total (of 60)** | **45** | **41** | **47** |

**Winner: Direction 3, Atelier Parchment.** It needs two grafts from Dawn Sky (the page-top daybreak haze and the dusk wash into the night footer) so it stops reading as a generic paper atelier and starts reading as Sky Fragrances.

## Direction 1: Porcelain and Gilt (45)

The most disciplined page of the three, and the easiest to picture on byredo.com.

- **Luxury, 8.** `home--desktop-fold.png` has true ink type on porcelain, one monogram-sized halo and a hairline above the COD line, and it is quiet in the right way. The white passe-partout mats around the dark renders (`home--desktop-full.png`, the Best Sellers grid) make the bottles look like prints on a gallery wall. The weakness is that the fold is close to sterile. Once the halo fades, the hero is a white page with a black button on it, and without the monogram it could belong to any DTC brand.
- **Coherence, 7.** The ink announcement bar and the inverted "Why choose us" night band tie it to the dark site. But the footer goes to stone `#EFECE5` (`home--desktop-full.png`, bottom; `checkout--desktop-full.png`), so the page ends on light. The dark house never gets the last word, and the whole site carries less of the dark theme's gold than either rival.
- **Typography, 9.** Setting "More Than Just A Scent" in ink instead of gold is the best typographic call in the set: at display size, Cormorant in ink looks like a museum label. Section eyebrows in gilt-700 and hairline rhythm between sections are both right. The one exception is the Longevity/Sillage meter on `pdp--desktop-full.png`, which renders as solid ink bars and is the heaviest thing on the PDP.
- **Gold and logo, 7.** The deepened header monogram holds at 40px, and the gilt lockup on stone in the footer is beautiful. On the other hand, gilt nearly disappears. The 1px gilt underline on the ink primary button cannot be seen at normal size (`home--desktop-fold.png`, "Explore the collections"), so the gold-gradient signature object is gone everywhere except inside the night band.
- **Imagery, 9.** The mats are the best framing of the three: white, even and crisp, with the same language on the PDP gallery (`pdp--desktop-fold.png`, 12 to 20px mat). Thumbnails are desaturated when inactive and take an ink border when active. Two misses: the Instagram strip is unmatted and its bottom edge sits on the section rule, and the Shop by Collection tiles are bare black slabs.
- **Originality, 5.** Gallery white is the default move for a light luxury theme, and nothing in it says sky.
- **Visible defects.** Checkout placeholders ("0300 1234567", "House, street, area") render in full ink and read as values the customer already typed, because there is no `::placeholder` rule (`checkout--desktop-full.png`). The review histogram tracks and the empty meter segments are invisible (`pdp--desktop-full.png`). The drawer veil turns the dark product image behind it a milky slate grey (`drawer--desktop.png`).

## Direction 2: Dawn Sky (41)

The best idea of the three and the weakest execution.

- **Luxury, 6.** The hero (`home--desktop-fold.png`) is the most atmospheric fold of the three, with a warm sun behind the monogram and a peach horizon. After that, the gold-gradient bar with a bronze drop shadow is used on every primary action: Explore, Start the Scent Finder, Subscribe, Add to Cart, Checkout and Place Order (`product_azure_oud--desktop-fold.png`, `checkout--desktop-full.png`, `drawer--desktop.png`). On a light ground a saturated gold slab with a shadow reads as a promo coupon, not foil, and none of Byredo, Le Labo or Aesop would repeat it six times.
- **Coherence, 8.** It keeps the most of the dark site: the gold-gradient title (deepened to bronze), the gold button, a #0A0A0A night footer and the ink night band. The dusk wash that deepens into the black footer (`home--desktop-full.png`, the Join the Sky List section; `faq--desktop-full.png`; `checkout--desktop-full.png`) is the most graceful light-to-dark handover in the set.
- **Typography, 7.** The type is sound. The bronze title works at display size, and fading gold-rule dividers replace the grey section borders. Rhythm is slightly noisier because the peach haze, the gold rules, the card shadows and the button shadows all compete with each other.
- **Gold and logo, 5.** The header monogram is the palest of the three. At 40px its highlights wash into the linen header (`home--desktop-fold.png`, `shop--desktop-fold.png`). The footer keeps `lockup-on-black`, and its black square is plainly visible against the #0A0A0A footer on both desktop (`home--desktop-full.png`, `faq--desktop-full.png`) and mobile (`home--mobile-full.png`).
- **Imagery, 6.** The cards are unframed dark tiles with a long soft shadow. The DIRECTION.md argues this makes them "night windows", but on ivory they read as holes, especially a row of four (`home--desktop-full.png`, New Arrivals). Thumbnails at 0.82 opacity go grey (`product_azure_oud--desktop-fold.png`), and the PDP main image has no mat at all.
- **Originality, 9.** Daybreak at the top of every page, dusk before the footer and night at the end is a real concept, specific to a house called Sky whose collections are times of day. The pearl-blue zenith is visible under the header on shop and FAQ (`shop--desktop-fold.png`, `faq--desktop-fold.png`). The empty For Him/For Her/Unisex tiles become little horizons, which is a lovely touch.
- **Visible defects.** The sort select lost its chevron (`shop--desktop-fold.png`). Review histogram tracks are invisible (`product_azure_oud--desktop-full.png`). The drawer close button keeps a heavy bronze focus box on open (`drawer--desktop.png`, `drawer--mobile.png`).

## Direction 3: Atelier Parchment (47)

This one is the same house as the dark site with the lights on, and the most complete of the three.

- **Luxury, 8.** Parchment with a barely-there laid grain, sepia ink and bronze (`home--desktop-fold.png`) is closer to Aesop's warm stone and Le Labo's lab paper than to a template. The ink primary button with a champagne label (`product_azure_oud--desktop-fold.png`, "Add to Cart") is the best-looking primary button in the set, and it is a piece of the night theme set into the day. Risk: over a whole page the parchment can tip into beige and vintage (`shop--desktop-full.png`), and it lowers the page's overall luminance, which makes the dark renders feel heavier.
- **Coherence, 9.** Every night device lands: the ink announcement bar, ink buttons with champagne labels, the "Why choose us" night band with a warm top glow (`home--desktop-full.png`), and the dark theme's own #0A0A0A footer with a clean lockup (`mix-blend-mode: lighten` removes the black square). The bronze title gradient is visibly the same object as the dark site's gold title. Put next to `baseline-dark/home--desktop.png`, it reads as one house.
- **Typography, 8.** The sepia ink `#1E1812` is softer than D1's true ink but still crisp. The COD trust line gets bronze hairlines on both sides of the hero like a seal (`home--desktop-fold.png`), which is the nicest small typographic gesture in the set. Meters and histogram tracks are properly tokenised, so bronze segments sit on visible empty tracks (`product_azure_oud--desktop-full.png`), which neither rival managed.
- **Gold and logo, 8.** The bronze-deepened monogram has enough weight in the hero and the header. The champagne Sale badge on the dark imagery (`home--desktop-full.png`, Silver Lining and Cumulus Cashmere) is a smart move, because gold is used where it has a dark ground to glow against. Deduction: the header monogram is slightly muddy at 40px because the CSS filter flattens the metallic gradient.
- **Imagery, 8.** The paper mounts (a 7px inset, a bronze hairline and a soft sepia shadow) turn the renders into specimen plates, and the PDP gallery and thumbnails share the language. The mounts are a little tight next to D1's and read as a border more than a mat, and the Instagram strip and collection tiles are not mounted.
- **Originality, 6.** An atelier on paper is a known genre, and it says perfumer but not sky.
- **Visible defects.** See the must-fix list below.

## Why Atelier Parchment wins

Porcelain and Gilt is the cleanest page, but it wins by subtraction. It removes the gold, ends each page light and could belong to any brand. Dawn Sky has the only concept that belongs to Sky, but its gold-bar buttons, pale monogram and boxed footer lockup cheapen it. Atelier Parchment is the one a luxury house could ship after a week of polish: the dark theme's objects (the ink, the champagne label, the black footer, the gold title) all come across intact, and the product plates are dignified. Its one real gap is that it does not say "sky", and Dawn Sky can supply that without adding a single new component.

## Grafts into Atelier Parchment

1. **Daybreak haze at the top of every page (from D2).** Lay D2's `--grad-dawn` over the top of `.site-main` (pearl-blue zenith `#EDF0F1` to peach `#F6E7D6`, faded out by about 28rem) on top of the parchment and under the grain, at about 60% of D2's strength. It is how every page says Sky at first glance. See D2's `shop--desktop-fold.png` and `faq--desktop-fold.png`, where the zenith is visible under the header.
2. **Dusk wash into the night footer (from D2).** D3 currently cuts from parchment to #0A0A0A with a hard edge (`home--desktop-full.png`, the Join the Sky List section; `checkout--desktop-full.png`; `faq--desktop-full.png`). Put D2's peach-deepening wash on the last section of each page, tinted toward warm stone, so every page runs daybreak, day, dusk, night. Together these two grafts give the theme an art-directed arc instead of a flat colour swap.
3. **Horizon gender tiles (from D2).** The For Him, For Her and Unisex tiles (D3 `home--desktop-full.png`) should take D2's `--grad-horizon` (pearl to peach) inside D3's bronze hairline border in place of the faint bronze radial. They are the one place the Sky idea can appear as an object.
4. **COD trust card on the PDP (from D2).** D2's `product_azure_oud--desktop-fold.png` puts the three trust rows on a pearl-to-champagne card with a bronze hairline, so Cash on Delivery reads as a promise. Graft it onto D3's paper (a vellum card) under the WhatsApp button.
5. **Wider mats on the PDP and grid (from D1).** D1's mat is `clamp(6px, 3.2%, 14px)` on cards and 12 to 20px on the PDP gallery (D1 `pdp--desktop-fold.png`). D3's 7px inset reads as a border. Take D1's proportions and keep D3's vellum colour and sepia shadow.
6. **Frosted drawer veil (from D1).** Replace D3's `rgba(30,24,18,.46)` sepia scrim, which muddies the page behind the drawer to brown (D3 `product_azure_oud--desktop-drawer.png`), with D1's frosted veil (a parchment-tinted `rgba(233,224,208,.6)` plus an 8px blur), so the page recedes into mist rather than dirt.
7. **Selected payment and chip language (from D1).** D1's white card with an ink border and a gilt inset on the left edge for the selected COD option (D1 `checkout--desktop-full.png`) is crisper than D3's stone fill. Use a vellum fill, an ink border and a bronze left-edge inset.
8. **Inactive thumbnails (from D1).** Desaturate the inactive gallery thumbnails instead of fading them, and keep D3's ink frame on the active one.

## Must-fix in Atelier Parchment

1. **Composition bottle smudge.** On `product_azure_oud--desktop-full.png` ("The Composition"), the bottle sits in a feathered dark radial that reads as a dark smudge on parchment. Either mount it on a vellum plate like the gallery, or give the light theme a bronze-on-transparent radial in multiply. The same bug exists in all three directions.
2. **Collection tiles are the heaviest mass on the page.** Shop by Collection (`home--desktop-full.png`, the first section below the hero) is about 650px of near-black, heavier than the night band that is meant to be the page's one dark beat. Mount these tiles with the same vellum passe-partout as product cards, and lift the bottom scrim to start at about 45% so each collection's sky colour shows.
3. **Instagram strip.** On `home--desktop-full.png` and `home--mobile-full.png` the six tiles are unmounted and their bottom edge sits directly on the section rule. Give them the paper mount and 2 to 3rem of bottom padding before the rule.
4. **Hard cut to the footer.** Fix this with graft 2. Without it every page ends in a band of parchment followed by a black wall.
5. **Mobile sticky add bar bleed.** On `product_azure_oud--mobile-full.png`, the stars and "5.0" show through the 95% paper sticky bar beside "Rs. 8,950". Make the bar opaque parchment, or raise the backdrop blur to 20px at 98%.
6. **Page luminance.** Across `shop--desktop-full.png` the parchment `#F3ECE0` plus the grain reads slightly grey-beige at arm's length. Lift `--surface` about 2% toward `#F5EFE4`, cap the grain alpha near .2, and keep the darker stone for the scent-finder band only.
7. **Header monogram.** At 40px, `filter: brightness(.52)` flattens the gradient into a muddy brown (`home--desktop-fold.png`, header centre). Ship the bronze monogram and lockup assets the DIRECTION.md already calls for (`#4E331A` to `#9A7442` to `#5E3F20`) and swap them by theme in `header.php`.
8. **The hero plates and WebGL ribbons are unverified.** The shots used `reducedMotion: 'reduce'`, so the `invert(1) hue-rotate(180deg)` plate filter chain was never seen. Render real sepia-on-transparent plates before shipping, and get the art director to sign off on the hero with motion on.
9. **Keep gold scarce.** Once the grafts are in, the only saturated gold on the parchment should be the champagne button label, the Sale badge and the monogram. Do not bring D2's gold-gradient button onto the day ground; it is kept for inside the night band only, as D3 already does.
