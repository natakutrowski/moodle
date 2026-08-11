# J14F — Promotions conditionnelles

Les règles client sont stockées dans `metadatajson.customereligibility`; aucune
migration de base de données n'est nécessaire.

## Exemple CampusFR : propriétaire du PDF des verbes

- Code : `VERBESPDF20`
- Produit remisé : `SUB.PLAN.30`
- Remise : 20 %
- Doit déjà posséder : `DIGITAL.VERBES-3E-GROUPE`
- Ne doit pas encore posséder : `SUB.PLAN.30`
- Limite par utilisateur : 1

Pour une application sans code, activer simplement « Automatique ». Le même
moteur d'éligibilité est utilisé.

## Règles disponibles en J14F

- utilisateur connecté ;
- possède un ou plusieurs produits ;
- ne possède pas un ou plusieurs produits ;
- combinaison « toutes » ou « au moins une » ;
- code manuel ou application automatique.

Les SKU restent visibles uniquement dans le CRM administrateur et les journaux,
jamais dans les messages affichés aux clients.
