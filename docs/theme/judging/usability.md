# Light theme judging: usability and conversion lens

Judge: usability and conversion. I looked at contrast and legibility, the hierarchy of price and Add to Cart, the Cash on Delivery trust line, how clear the checkout form is, mobile spacing and tap targets, visual noise, and how each direction will hold up once real bottle photography on white or neutral backgrounds replaces the dark sample renders.

Method: I read each DIRECTION.md and prototype.css and looked at every listed screenshot. For the full-page shots I also made side-by-side strips with the dark baseline as a fourth column on mobile (scratchpad `theme/judge-usability/cmp2/`), plus close crops of the PDP trust rows, the mobile sticky Add bars, the footer newsletter and the drawers. I recomputed every contrast ratio cited below with the WCAG 2.x formula.

## Verdict

**Winner: Direction 1, Porcelain and Gilt (57/70).** Direction 3, Atelier Parchment, scores 48 and Direction 2, Dawn Sky, scores 47.

Direction 1 is the one a shopper can use without thinking about it. Ink type sits on near-white porcelain at 17.3:1, controls are white with 3.7:1 borders, the Add to Cart and Place Order buttons are the only solid dark blocks on a light page, and the white passe-partout is the only mount that will still look right when real white-background bottle photography arrives. It also has the least visual noise. It has the most small defects of the three, though (an invisible sort border, invisible review bars, a gilt "In stock" dot and a placeholder that looks like entered data), and all of them are cheap to fix. They are listed under must-fix.

## Scores (1 to 10 per criterion)

| Criterion | 1 Porcelain and Gilt | 2 Dawn Sky | 3 Atelier Parchment |
|---|---|---|---|
| Contrast and legibility | 8 | 7 | 7 |
| Price and Add to Cart hierarchy | 8 | 7 | 8 |
| Cash on Delivery trust line | 7 | 8 | 7 |
| Checkout form clarity | 8 | 6 | 7 |
| Mobile spacing and tap targets | 8 | 7 | 6 |
| Visual noise (lower noise scores higher) | 9 | 6 | 6 |
| Holds up with real photography on white or neutral | 9 | 6 | 7 |
| **Total (of 70)** | **57** | **47** | **48** |

## Direction 1: Porcelain and Gilt (57)

**Contrast and legibility, 8.** Text is #121110 on #F7F5F0 (17.31:1), muted text is #5F5A52 (6.28:1) and the accent is #6E5027 (6.79:1). Everything a shopper reads clears 4.5:1 with room to spare, including the eyebrows and step numerals. The points come off for three misses visible in the shots:
- The desktop Sort select on shop--desktop-fold keeps `border-color: var(--border)`, which is 1.26:1 for a control (the 3:1 non-text minimum applies). Its chevron is still the dark theme's #D4B084 stroke at 1.86:1.
- The review distribution bars on pdp--mobile-full and pdp--desktop-full are invisible. The base track is `rgba(245,240,232,.10)` and the prototype never re-tints it, so rows 4 to 1 show only numbers with no track.
- The placeholder "0300 1234567" in checkout--mobile-fold is `--text-muted` at 6.84:1 on white. It reads like a phone number someone has already typed.

**Price and Add to Cart, 8.** On pdp--desktop-fold the price is ink Cormorant, the size chips are white with an ink selected border, and Add to Cart is a full-width ink block with a gilt underline. The CTA is the only dark filled object in that column, so it wins the eye immediately. On pdp--mobile-full the sticky bar's ink ADD button is the clearest of the three. Sale prices use gilt-700 with a muted strike, which is clear. It loses a little because the ink CTA shares its colour with the nav and headings, so it has no hue of its own the way the dark site's gold bar does.

**Cash on Delivery, 7.** COD sits in the ink announcement bar at 12.55:1, the best position on every page. In the hero (home--desktop-fold) it is a 13px muted line under a hairline, which is dignified but the quietest element in the hero. On the PDP it is a plain icon row. At checkout it is the best of the three. The selected COD card (checkout--mobile-full) is a white card with an ink border and a gilt left edge, so "this is how you pay" is obvious.

**Checkout form clarity, 8.** White fields on porcelain with #8A847A borders (3.71:1), ink labels, clear 1-2-3 gilt step numerals, and an order summary on a raised white card. The field outlines are the crispest of the three. The only faults are the dark placeholder above and "Free" delivery set in gilt (`.summary__value--free` remapped to accent) rather than a success colour.

**Mobile, 8.** Spacing and tap targets match the dark site (46px qty buttons, large size chips, 52px CTAs). The sticky bar is 96% opaque and shows only a faint ghost of the rating text scrolling underneath (sticky-cmp crop), which is the mildest ghosting of the three. The WhatsApp FAB overlaps the accordion "+" on faq--mobile-fold and the email field on checkout--mobile-fold. That overlap is pre-existing and shared by all three directions and the dark theme, but a white disc on a light page makes it more noticeable.

**Visual noise, 9.** Flat porcelain, hairlines, one ink band per page. Nothing competes with product or price.

**Photography, 9.** Every image sits in a white mat with an inset hairline (shop--desktop-fold, home--desktop-full). The dark sample renders read as framed prints, and real white-background packshots will read as clean white cards on a porcelain wall. That is exactly how Aesop and Byredo grids convert.

## Direction 2: Dawn Sky (47)

**Contrast and legibility, 7.** Body text is fine (16.78:1), but several numbers are only just passing: the accent #82603A at 5.12:1 falls to 4.68:1 on the sand ground, the stars are #A87F4E at 3.24:1, and the bronze hero title's lightest stop is 3.20:1 on the peach haze (large text, so it passes, barely). Two real failures:
- The input placeholder #8A8175 on pearl is 3.77:1, so the "0300 1234567" format hint on checkout--mobile-full does not meet 4.5:1.
- `.toolbar__select` gets `background: var(--field-bg)`, a shorthand that wipes the chevron `background-image`. On shop--desktop-fold the Sort control has no dropdown arrow at all.

**Price and Add to Cart, 7.** The gold gradient bar with an ink label (5.96:1 at its darkest stop) is the most eye-catching CTA of the three and the closest sibling to the dark site. But price loses its authority. The drawer subtotal, checkout total and sticky bar price are bronze (#6E5028), the same family as sale prices, so "the price" and "the discount" share a colour. Card names also turn gold on hover (shop--desktop-fold, Aurora Bloom), which competes with the sale-price colour.

**Cash on Delivery, 8.** Best on the PDP. The pearl-to-champagne trust card with a bronze hairline (pdp-trust crop, product_azure_oud--desktop-fold) makes COD read as a promise, not fine print. The hero line is still a quiet 13px slate line, and the checkout COD option uses a taupe fill that looks more disabled than selected.

**Checkout form clarity, 6.** Pearl fields (#FFFDF9) on ivory that is fading to peach barely separate from the ground at a glance. Only the 3.61:1 border carries the field. Add the failing placeholder and the bronze total, and this is the weakest checkout. Its "Free" in success green is right.

**Mobile, 7.** Same layout, but the sticky bar ghosts more (the "(1) 5.0" rating shows through), and the long shadows under every card make a 2-up grid feel heavier.

**Visual noise, 6.** Gradient haze at every page top, dusk wash at every page bottom, a gold-rule divider between sections, and soft shadows under every card and button. Each one is pretty, but together they give the eye something to process on every scroll, and nothing is left flat for the product.

**Photography, 6.** Images have no mat, only a shadow on a moving gradient ground. Real white-background packshots would show as white rectangles against warm peach at the top of the page and ivory lower down, so their edges would read differently at different scroll depths. The footer keeps `lockup-on-black`, and its square edge is visible on home--mobile-full and product_azure_oud--desktop-full.

## Direction 3: Atelier Parchment (48)

**Contrast and legibility, 7.** Sepia ink on parchment (14.97:1), walnut muted (6.39:1) and a 4.95:1 placeholder, which is the best placeholder of the three. The grain adds texture behind every line of body text and softens the letterforms of the 13px Jost meta. There is one real failure: the footer newsletter's Subscribe button is #1E1812 on the #0A0A0A footer (1.13:1). On product_azure_oud--desktop-full and checkout--mobile-full it reads as an empty dark box with a thin gold edge, and the vellum email field above it is the brightest thing in the footer.

**Price and Add to Cart, 8.** An ink block with a champagne label (11.91:1) and a gold hairline. Price stays ink and the sale badge is champagne, so it separates from NEW. The hierarchy is as good as Direction 1. The label is a little softer because champagne on sepia is less crisp than porcelain on ink.

**Cash on Delivery, 7.** The hero line is bracketed by bronze rules like a seal on desktop (home--desktop-fold). That is a nice touch but it stays small. The PDP row is plain, and the checkout COD option is a stone fill with a bronze border.

**Checkout form clarity, 7.** Vellum fields on grained parchment, 3.20:1 borders and a good placeholder. The fields separate from the ground less than D1's white-on-porcelain, and the grain behind the summary card adds texture where a customer is reading totals.

**Mobile, 6.** The sticky bar at 95% shows the most ghosting of the three. On the sticky-cmp crop the product name and rating visibly overprint the bar behind "Azure Oud · Rs. 8,950". The grain also shows as speckle on phone screens (home--mobile-full).

**Visual noise, 6.** Grain everywhere, paper mounts, bronze-bordered gender tiles, shadows under mounts. It is the most "designed" page, and also the busiest.

**Photography, 7.** The paper mount frames the dark renders better than D2 does (shop--desktop-fold). But the mount is cream vellum on cream parchment, so a real pure-white packshot would sit as a brighter white rectangle inside a yellower frame, which can make the paper look dirty next to it. It works for dark or styled photography and is risky for white e-commerce shots.

## Grafts into the winner (Direction 1)

1. **COD trust card from Direction 2** (product_azure_oud--desktop-fold). Wrap D1's PDP trust list in a white card with a `--border-accent` hairline and the gilt icon, COD first. Keep it white, not the D2 gradient, so it matches D1's mats. This is the single biggest trust-conversion gain available.
2. **Success green for stock and free delivery, from Directions 2 and 3.** `.stock-line::before` is gilt-600 in D1 (pdp--desktop-fold "In stock") and `.summary__value--free` is accent (checkout--desktop-fold "Free"). Use `--success` #1F6B3E (6.50:1 on white) for both. Green means available and free, and gilt means brand.
3. **Champagne sale badge from Direction 3** (shop--mobile-full, "SALE -15%"). D1's ink badge on a near-black render has no visible edge. Use #D4B084 fill with a #121110 label (9.29:1) for `.badge--sale`, and keep ink for `.badge--save`, so SALE and NEW are told apart at a glance.
4. **Placeholder tone from Direction 3** (4.95:1). In D1, set `.field__input::placeholder` to about #7A746A (4.63:1 on white) so hints look like hints and are never mistaken for entered data.
5. **Visible meter tracks from Direction 3.** `.review-summary__track, .meter__seg { background: rgba(18,17,16,.08) }` with the fill in gilt-600, so the rating distribution on the PDP reads again.
6. **Modal scrim from Direction 3, at D1's own strength.** Replace the frosted porcelain veil (drawer--desktop) with D1's unused `--scrim-modal` rgba(30,27,22,.38) and a 2px blur. The veil turns the page into grey mist and the drawer edge nearly disappears into it. A warm dark scrim gives the cart drawer clear modal focus.
7. **Hero COD seal from Direction 3.** On desktop, bracket the hero trust line with short gilt rules on both sides instead of D1's single rule above, and set it in `--text` rather than muted. On mobile keep one rule above and keep "Rs. 3,000" from wrapping onto its own line (home--mobile-fold currently orphans it).

## Must-fix in the winner before shipping

1. **Sort select border and chevron** (shop--desktop-fold). `html[data-theme="light"] .toolbar__select` sets `border-color: var(--border)`, which is 1.26:1. Change it to `var(--rule-control)` (3.71:1). Redraw the chevron data-URI stroke from `%23D4B084` (1.86:1) to `%236E5027` (6.79:1) for both `.toolbar__select` and `.field__input--select`, under the light theme only.
2. **Select chevron wiped on the contact page.** D1's `.field__input { background: var(--porcelain-000) }` is a shorthand that removes the `.field__input--select` chevron `background-image` (used in contact.php). Use `background-color` instead. Direction 2 has the same bug on the shop Sort control, and it is visible there.
3. **Invisible review distribution bars** (pdp--desktop-full, pdp--mobile-full). Re-tint `.review-summary__track` and `.meter__seg` (graft 5). Check `.star-input__label` too, which is still `rgba(245,240,232,.16)` and invisible on white, so the Write a review stars will be invisible.
4. **"In stock" dot and "Free" in gilt** (pdp--desktop-fold, checkout--desktop-fold). Switch both to `--success` (graft 2).
5. **Placeholder too dark** (checkout--mobile-fold, "0300 1234567" at 6.84:1). Lighten it to about #7A746A (graft 4).
6. **Sticky Add bar and filter bar ghosting** (pdp--mobile-full, sticky-cmp crop). At 96% opacity the Cormorant rating and name show through. Make `.sticky-bar` and `.filter-bar` fully opaque #FCFBF8 and keep the upward shadow. All three directions have this, and D1 has the least.
7. **Sale badge disappears on dark renders** (shop--desktop-full, home--desktop-full "SALE -20%"). Use the champagne sale badge (graft 3).
8. **Cart drawer overlay too pale** (drawer--desktop, drawer--mobile). Use the warm scrim (graft 6). The drawer panel (#FCFBF8) against a blurred porcelain veil has almost no edge, and the product image behind turns flat grey.
9. **Hero COD line is the quietest thing in the hero** (home--desktop-fold, home--mobile-fold). Promote it as in graft 7. COD is the main reason a Pakistani first-time buyer will risk an unknown perfume brand, so it should not be the lightest text above the fold.
10. **WhatsApp FAB overlap** (faq--mobile-fold covers the "+" toggle, checkout--mobile-fold covers the email field's right edge). This is pre-existing in both themes, but on light the white disc and shadow make the collision more visible. Hide the FAB on checkout (the page already offers WhatsApp support in the summary) and add bottom padding equal to the FAB height on FAQ and PDP mobile.
11. **White-background photography.** When real packshots on white replace the renders, drop `--mat` padding to 0 for those images and keep only the inset hairline and hover shadow. Otherwise a white photo inside a white mat becomes a double frame with dead margin. The token already exists, so this is a one-line switch per image type.

## Notes for the other judges

- All three directions share the COD announcement bar in ink, and it is the right call: it is the highest-contrast line on every page in all three.
- On a pure conversion reading, Direction 2's gold Add to Cart is the stronger CTA object. If the owner misses the gold bar, a middle path is D1's ink button with the champagne label (#F2E6D2, 15.3:1 on ink) instead of porcelain, which keeps the hierarchy and brings the brand hue back into the one object that sells.
- Direction 3's footer Subscribe (1.13:1 against the footer) and Direction 2's missing Sort chevron are both genuine usability defects. If either direction is chosen, fix those first.
