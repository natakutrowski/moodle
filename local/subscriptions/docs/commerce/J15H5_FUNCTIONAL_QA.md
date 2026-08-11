# CampusFR Commerce 7.95 — J15H.5 Functional QA

This checklist certifies the complete Commerce journey after J15H.1–J15H.4.
Run it on a staging environment using dedicated Stripe and Alfa test accounts.

## Test data

Prepare:

- one new email address never used in Moodle;
- one existing CampusFR account with a password;
- one account owning the PDF only;
- one account owning the course only;
- one account owning the complete bundle;
- one active course product, one digital product and one bundle;
- EUR and RUB prices;
- Stripe test mode and Alfa test mode enabled.

Record for every test:

- date and tester;
- browser and device;
- order reference;
- expected result;
- actual result;
- screenshot or log when failed.

## A. Public discovery

### A1. Showroom — guest

- Open the Showroom in FR, EN and RU.
- Verify Hero, video, offers, FAQ and CTA.
- Verify the offer order on mobile: Bundle, Course, PDF.
- Verify stack and slider modes from the Builder.
- Change EUR/RUB and verify all displayed prices.
- Verify the Bundle badge, halo and hover on desktop.
- Verify large-display spacing on a 4K/5K screen.
- Verify no horizontal page overflow.

### A2. Storefront

- Open the Boutique in FR, EN and RU.
- Verify every product card and responsive cover.
- Verify the compact currency selector and flags.
- Verify “Choix de Gustave” on mobile.
- Verify product details and recommendations.
- Verify no Mustache exception is raised.

## B. Cart and checkout

### B1. Cart

- Add one product from the Showroom.
- Add another compatible product from the Boutique.
- Change currency and verify unavailable items are handled explicitly.
- Remove and restore items.
- Verify totals and responsive thumbnails.

### B2. Guest checkout — Stripe

- Start with a brand-new email address.
- Complete identity fields and accept legal terms.
- Verify provider modal and currency.
- Complete the Stripe test payment.
- Verify a single order is created.
- Verify the order status and fulfillment status.
- Verify the transactional email and invoice.

### B3. Guest checkout — Alfa

- Repeat B2 with Alfa test mode.
- Verify VPN warning and provider-specific copy.
- Verify RUB handling and final amount.

### B4. Connected checkout

- Repeat with an existing connected user.
- Verify identity fields are not requested again.
- Verify Checkout Express when legal terms are already accepted.

## C. Post-purchase provisional account

### C1. Order result

- Complete a guest purchase with a new email.
- Verify the account-finalization dialog opens.
- Choose “Later”.
- Verify “Retrouver mes ressources” is hidden.
- Verify “Voir la commande” remains available.
- Verify course access opens the account-finalization dialog instead of login.
- Verify digital downloads remain available when intended.

### C2. Order details

- Open order details from the same guest browser session.
- Verify the order is visible without a permission error.
- Verify course buttons open the same shared dialog as Order Result.
- Verify invoice, print and allowed downloads.
- Verify the key icon spacing.

### C3. Account activation

- Open every “Finaliser mon compte” action.
- Verify the URL contains `#activation`.
- Verify the page lands near the activation card.
- Verify no Edly page banner appears.
- Verify the premium single-card layout and continuous background.
- Create the password.
- Verify the account becomes usable immediately.
- Verify provisional navigation disappears.

### C4. Login protection

- Before finalization, manually open `/login/index.php` in the same browser.
- Verify the provisional-account notice is displayed.
- Verify its action opens the secure activation flow.
- After finalization, verify the notice no longer appears.

## D. Fulfillment

### D1. Course product

- Purchase the standalone course.
- Verify successful course access fulfillment.
- Verify course appears in Mes cours.
- Verify desktop and mobile course covers.
- Verify access button opens the course.

### D2. Digital product

- Purchase the standalone PDF.
- Verify it appears in Mes ressources.
- Verify secure download, counter and last-download timestamp.
- Verify responsive resource cover.

### D3. Bundle

- Purchase the complete bundle.
- Verify all components are listed with translated commercial names.
- Verify course and PDF fulfillment.
- Verify components in Order Result, Order Details and Mes achats.

### D4. Conditional promotion

- Use an account already owning the PDF.
- Verify the configured Bundle discount is offered.
- Verify the final total and invoice.
- Verify the promotion is not applied to an ineligible account.

## E. My Campus pages

### E1. Mes achats

- Verify translated product names and Paid badge alignment.
- Verify the discreet Boutique link.
- Verify no misleading large “Buy” button in empty subsections.
- Verify long titles on mobile.

### E2. Mes ressources

- Verify Native and Legacy items.
- Verify thumbnails, accessible alt text and download links.
- Verify file size, counters and dates.

### E3. Mes cours

- Verify compact desktop cards and 4:5 visual area.
- Verify dedicated 4:3 mobile cover and Moodle fallback.
- Verify recommendations have no empty top gap.

## F. Transactional communication

- Verify order confirmation email in FR, EN and RU.
- Verify course, digital and bundle variants.
- Verify invoice PDF attachment.
- Verify technical reference and CTA.
- Verify BCC journal delivery.
- Verify queue idempotency and retry behavior.

## G. Accessibility

- Complete Showroom, Boutique, Cart and Checkout using keyboard only.
- Verify visible focus, logical order and skip link.
- Verify modals announce title and description.
- Verify focus returns to the triggering control.
- Verify required fields and validation announcements.
- Test reduced motion and forced-colors mode where available.

## H. Performance and resilience

- Verify only the main LCP image receives high priority.
- Verify other covers lazy-load.
- Verify the video does not preload full media before interaction.
- Verify responsive derivatives are served for Showroom, Storefront, Checkout and Resources.
- Replace a product image and verify a new derivative hash is generated.
- Run the Moodle cron and verify no Commerce task fails.
- Review PHP, web server and Moodle logs after the complete campaign.

## I. Regression matrix

Repeat the critical purchase journey on:

- desktop Chrome;
- desktop Safari or Firefox;
- iPhone 13 Safari;
- Android Chrome;
- tablet portrait and landscape;
- 4K/5K desktop display.

## Certification decision

The build can pass J15H.5 when:

- no critical or major defect remains;
- no duplicate payment or fulfillment is observed;
- every provisional-account journey avoids the classic login dead end;
- FR/EN/RU and EUR/RUB are validated;
- Stripe and Alfa test transactions both succeed;
- all PHPUnit suites pass;
- logs contain no new warnings or exceptions caused by Commerce.
