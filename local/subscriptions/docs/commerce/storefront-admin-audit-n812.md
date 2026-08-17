# Commerce 7.95N8.12 — Storefront admin audit

This audit records the current Page Boutique settings before the N8.13 Builder rewrite.

| Admin concept | Stored field(s) | Current runtime consumer | N8.12 decision |
|---|---|---|---|
| Page template / layout | `storefront.template`, `commerce_position`, `global_zones` | `CommerceStorefrontPageResolver`, Storefront page presenter | Keep; move to Presentation |
| Moodle shell | `shell_mode`, `show_header`, `show_footer`, `product_header_mode`, `theme` | Storefront page resolver / public product page | Keep; advanced Presentation |
| Editorial sections | locale `sections` | Storefront page presenter / section renderers | Keep; Content Builder |
| Featured / order / badges | `storefront.merchandising` | Storefront repository / catalogue cards | Keep; Distribution |
| Experience group / trust / quick facts | `storefront.experience` | `CommerceStorefrontExperienceResolver` | Keep; simplify with automatic defaults in N8.14 |
| Recommendations | `storefront.recommendations` | recommendation resolver | Keep; Distribution, names must resolve through ProductNameResolver |
| SEO | locale `seo` | `CommerceStorefrontSeoPresenter` | Keep; default-first UX in N8.14 |
| Public slugs | `storefront.routing.slugs` | product slug / routing services | Keep; advanced Distribution |
| Showroom association | `showroom.key` | discovery URL resolver / showroom surfaces | Keep; Distribution |
| Discovery destination | `showroom.discoverymode` | `CommerceProductDiscoveryUrlResolver` | Keep; express as a business choice |
| Storefront CTA from showroom | `showroom.showstorefrontcta` | showroom/storefront CTA logic | Keep; simplify label |
| Showroom media / alt | `showroom.mediaitemid`, locale alt | showroom media service | Keep for compatibility; candidate for Media & Files later |
| Locale copy / AI translation | locale tools | locale transfer / AI translation services | Keep; Tools |
| Import/export | `.cfrproduct` package | package service | Keep; Tools |
| Reset | Storefront reset service | admin-only maintenance | Keep; Tools / destructive action |

## N8.12 architecture

The Page Boutique entry point is split into four business areas:

1. Content
2. Presentation
3. Distribution
4. Tools

The existing full editor is preserved temporarily as `storefront_builder.php`. N8.13 will replace its Content Builder with the new block-first editor, including graphical layout previews and improved per-block previews.
