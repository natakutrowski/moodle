# CampusFR Advent V4 — Design Bridge 0.3.0, DEV only

## Pourquoi V3 n'a pas suffi

Sur le rendu Moodle, les bordures jaunes et roses sont trop discrètes ou absentes. Le correctif V3, limité aux variables, ne garantissait pas une priorité de style supérieure aux utilitaires Tailwind injectés dans le Shadow DOM.

V4 utilise des classes sémantiques ciblées et un petit CSS **non layerisé, ajouté à la fin de `dist/style.css` après compilation** par `scripts/finalize.mjs`. C'est la même stratégie de delta CSS compilé qui avait permis d'ajuster le showroom Articles, sans changer le checkout.

## Éléments corrigés

- Contours or (pastilles hero, portes, bloc d'écoute, cartes formats, photographie du chat, compte à rebours, contacts).
- Contours roses (sur-titres, fiche produit).
- FAQ : cadre et réponses en or, séparateurs pleins, curseur main et focus clavier.
- Suppression du logo CampusFR du Hero ; topbar Moodle inchangée.
- Titres des 24 contes et 5 phrases manuscrites toujours en FR pour tous les visiteurs.
- Pas de changement aux prix, au footer légal, au checkout, aux médias CRM ni au chantier cadeau.

## Limitations médias

Le ZIP Lovable d'origine contient les fichiers `.asset.json` de 7–24, mais pas leurs binaires `.webp`; le MP3 est aussi absent. La page doit utiliser les vrais fichiers fournis via CRM, sans inventer d'images.

## Compiler

```bash
python3 scripts/audit_source.py
bash scripts/build-dev-isolated.sh
```

Après compilation, vérifier `CAMPUSFR_ADVENT_BRIDGE_VISUAL_V4` dans le `dist/style.css` obtenu, puis empaqueter avec `bridge_lovable.py pack` et installer uniquement dans `advent_calendar_2026` avec `--replace` et SHA-256. Garder DEV, sans toucher PROD.

## Vérification et rollback

L'audit est statique, pas un test navigateur E2E. Après installation, vérifier les différents blocs en aperçu DEV et garder la sauvegarde du Design Bridge avant de promouvoir.

Le script de packaging fourni évite les longs copier-coller :

```bash
bash scripts/pack-dev.sh /tmp/campusfr-advent-build.XXXXXXXX
```

Il effectue les validations du manifeste et du CSS compilé puis affiche le chemin `DESIGN_PACK` et son SHA-256. Il n'installe rien dans Moodle.
