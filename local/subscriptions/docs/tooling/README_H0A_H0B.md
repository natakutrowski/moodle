# Phase 7.94H0A — Inventaire Tests et CLI

Cet inventaire est généré à partir de `subscriptions(53).zip`. H0A ne modifie aucun script CLI.

## Résumé

- Tests PHP totaux : **168** avant copie H0B.
- Tests hors `tests/commerce/` : **45**.
- Tests PHP à la racine : **18**.
- Fichiers dans `cli/` : **144**.

## Décisions H0B

| Source | Destination |
|---|---|
| `tests/alfa_commerce_payment_provider_test.php` | `tests/commerce/checkout/alfa_commerce_payment_provider_test.php` |
| `tests/stripe_commerce_payment_provider_test.php` | `tests/commerce/checkout/stripe_commerce_payment_provider_test.php` |
| `tests/legacy_alfa_payment_gateway_test.php` | `tests/commerce/legacy/legacy_alfa_payment_gateway_test.php` |
| `tests/legacy_stripe_payment_gateway_test.php` | `tests/commerce/legacy/legacy_stripe_payment_gateway_test.php` |
| `tests/legacy_commerce_payment_request_factory_test.php` | `tests/commerce/legacy/legacy_commerce_payment_request_factory_test.php` |
| `tests/legacy_payment_request_adapter_test.php` | `tests/commerce/legacy/legacy_payment_request_adapter_test.php` |
| `tests/digital_purchase_handler_test.php` | `tests/commerce/purchase/digital_purchase_handler_test.php` |
| `tests/subscription_purchase_handler_test.php` | `tests/commerce/purchase/subscription_purchase_handler_test.php` |
| `tests/crm_commerce_customer_service_test.php` | `tests/crm/commerce/crm_commerce_customer_service_test.php` |
| `tests/crm_commerce_shadow_service_test.php` | `tests/crm/commerce/crm_commerce_shadow_service_test.php` |
| `tests/crm_commerce_snapshot_comparator_test.php` | `tests/crm/commerce/crm_commerce_snapshot_comparator_test.php` |
| `tests/crm_native_runtime_customer_service_test.php` | `tests/crm/commerce/crm_native_runtime_customer_service_test.php` |
| `tests/legacy_crm_commerce_revenue_test.php` | `tests/crm/commerce/legacy_crm_commerce_revenue_test.php` |
| `tests/safe_crm_commerce_customer_service_test.php` | `tests/crm/commerce/safe_crm_commerce_customer_service_test.php` |
| `tests/student_commerce_purchase_collection_test.php` | `tests/commerce/runtime/student_commerce_purchase_collection_test.php` |
| `tests/crm_business_rules_test.php` | `tests/crm/business/crm_business_rules_test.php` |
| `tests/currency_test.php` | `tests/currency/currency_test.php` |
| `tests/subscription_config_test.php` | `tests/config/subscription_config_test.php` |

## Inventaire CLI provisoire

Les classifications CLI sont provisoires. Elles servent de base à H0C et ne provoquent aucun déplacement dans ce delta.

| Action | Nombre |
|---|---:|
| `MANUAL_REVIEW` | 39 |
| `MOVE` | 105 |

Les inventaires détaillés sont disponibles en JSON et CSV dans ce dossier.
