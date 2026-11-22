# CampusFR — Calendrier de l’Avent 2026 — transmission DEV

## Version figée
- Site cible : **DEV seulement** (`/var/www/moodle-dev`) ; ce dossier Git ne vaut pas autorisation PROD.
- Design Bridge : `advent_calendar_2026`, manifeste **0.3.1**.
- Showroom CMS : **#9**, clé `avent_2026_dev` ; publication pilotée depuis le CRM.
- Produit Commerce : **#39**, SKU `AVENT_2026`, prix actifs **15 EUR / 1 500 RUB**.
- Règle d’accès : `reader:publication:avent-2026`.
- Slugs : FR `/calendrier-avent-2026`, EN `/advent-calendar-2026`, RU `/advent-kalendar-2026`, alias `/avent2026`.
- Source de vérité du tarif, vendeur légal, checkout et droits : **Commerce**.
- Les 24 couvertures sont incluses dans le design. L’audio d’exemple est géré par la Moodle File API via le CRM (pas dans `dist`).
- Test utilisateur rapporté : EUR et RUB fonctionnels, changement sans rechargement et footer mis à jour ; tests de paiement en DEV, y compris RUB en showroom publié.

## Frontières et vigilance
- Le Gate de checkout de recette est limité à la prévisualisation administrateur **DEV / brouillon #9** ; le parcours **public publié** suit les règles normales de Commerce. Ne pas assimiler les deux.
- Les paramètres CMS, SEO, produits, tarifs et fichiers Moodle sont dans **la base / le filepool**, pas créés par ce commit Git. Prévoir une migration ou export sécurisé et idempotent au moment du déploiement cible.
- Aucune donnée de commande, utilisateur, clé Stripe ou secrets n'est versionnée ici.
- Le parcours **« offrir à un ami »** est un **chantier indépendant** à développer après ce socle.
- **Ne pas inclure** la modification Reader `CommerceStorefrontUrlResolver.php` dans ce commit : elle relève du chantier Reader.
- Ne pas lancer `git add -A` dans le dépôt DEV partagé.

## Reconstruction du design
- Source compilable dans ce dossier ; voir `scripts/build-dev-isolated.sh`.
- La compilation Bridge exclut le MP3, qui est fourni côté CMS via `previewAudioUrl`.
- Les images originales des couvertures 7 à 24 sont dans `src/assets`.
- Un nouvel import doit augmenter strictement `manifest.version` et coordonner les tests du Gate DEV.
- Le CSS comprend les correctifs de contours Shadow DOM, la suppression du logo secondaire, et la FAQ avec curseur main.

## Transmission au chantier Cadeaux
- Réutiliser le checkout et les droits Commerce existants, sans modifier le produit pour le cadeau.
- Ne pas confondre le **payeur** avec le **destinataire** du droit Reader.
- Planifier activation cadeau avec émission/réclamation, idempotence, révocation et audit séparément, sans modifier les achats nominaux.
