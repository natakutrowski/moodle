# Phase 7.94H0 — Politique des outils techniques

## Objectif

Les scripts CLI doivent être classés par domaine, rester de petits points d'entrée et être sûrs par défaut.

## Catégories

- `commerce/audit` : diagnostics en lecture seule.
- `commerce/certification` : certifications globales et contrôles de phase.
- `commerce/migration` : migrations et réconciliations, dry-run par défaut.
- `commerce/operations` : opérations administratives explicites.
- `commerce/reporting` : rapports et exports.
- `crm/*` : outils CRM regroupés par fonction.
- `maintenance/*` : maintenance technique générique.
- `development` : outils réservés à DEV.

## Conventions obligatoires

1. Tout exécutable PHP définit `CLI_SCRIPT` avant de charger Moodle.
2. Tout exécutable PHP charge explicitement `$CFG->libdir . '/clilib.php'`.
3. Les opérations d'écriture doivent être explicites et documentées.
4. Les nouveaux scripts ne doivent pas être ajoutés à la racine de `cli/`.
5. Aucun fichier HTML, brouillon ou preuve de concept ne doit vivre dans `cli/`.
6. Les outils ponctuels doivent être supprimés après leur remplacement par un auditeur durable.

## Suppressions H0D

H0D retire uniquement des outils ponctuels ou clairement remplacés : anciens audits I10C–I10F, scripts d'application H0 consommés, ancien audit filedir remplacé par V2 et POC HTML.
