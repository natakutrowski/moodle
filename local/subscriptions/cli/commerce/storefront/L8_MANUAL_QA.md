# CampusFR Commerce — L8 final QA checklist

This checklist is intentionally limited to the Storefront / Showroom delta L.

## Locales
Run each public journey in FR, EN and RU.

## Viewports
Run the visual checks on:
- desktop;
- tablet;
- mobile (~390 px).

## Product types
Validate:
- Digital/PDF;
- Course;
- Bundle.

## Customer states
For each relevant product validate:
- guest;
- connected non-owner;
- owner.

Expected owner destinations:
- Course → course;
- Digital/PDF → customer digital resources;
- Bundle → Mon Campus.

## Pricing
Validate:
- EUR;
- RUB;
- normal price;
- promotional/comparison price where configured.

## Purchase
Validate:
- add to cart;
- remove from cart;
- buy now;
- checkout return;
- owned product no longer exposes public purchase controls.

## Discovery/navigation
Validate:
- Boutique → discovery target;
- Showroom → Storefront details;
- Storefront opened from Showroom → Back to presentation;
- Storefront opened from Boutique → Back to shop;
- currency change preserves the origin context;
- no Showroom can route a CTA back to itself;
- current product never recommends itself;
- owned products are excluded from public recommendations.

## Builder/localisation
Validate:
- RU locale can be copied to FR and EN;
- OpenAI translation produces a preview before apply;
- cancel/preview does not overwrite saved content;
- FR/EN/RU retain independent localized content;
- `product_header_mode=builder` does not resurrect the automatic product presentation;
- `commerce_position=none` does not resurrect the automatic commerce block.

## DEV → PROD portability
Export one completed Storefront as `.cfrproduct` and import it on a disposable matching product.
Validate:
- sections;
- locales;
- media;
- layout;
- Showroom link;
- no duplicated sections.
