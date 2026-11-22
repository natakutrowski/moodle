# CampusFR — Showroom « Calendrier de l’Avent » (Lovable → Design Bridge)

**État : adaptation source corrigée (dépendances compatibles Node 22.11), prête pour compilation et recette DEV. PAS de build Vite certifié, PAS de déploiement, PAS de validation Stripe.**

## Fonctionnement

- Design Lovable conservé (héros, 24 portes réversibles, animation neige, boîtes cadeau, FAQ, compte à rebours).
- Contenus localisés dans `content/{fr,en,ru}.json` : **125 clés par langue**, dont les 24 titres. Moodle sélectionne la langue, pas un paramètre `lang` arbitraire dans l'URL.
- Les 2 affichages de prix partagent l’état React. Le `<select>` de devise est à côté/sous le prix dans l’offre ET le bloc cadeau.
- Le changement de devise reprend le mécanisme du showroom Articles : `fetch` de l’URL officielle fournie par Commerce dans `commerce.currencies[].url` (même origine/route), contrôle du payload SSR, mise à jour simultanée des prix/CTA et remplacement du footer HTML légal **généré par Moodle/Commerce**, puis `history.replaceState`. **Aucun rechargement de page**.
- Il n’y a **aucune conversion de devise côté navigateur** et aucun prix fixe. Le bouton d’achat utilise uniquement `sku`, `priceid`, `sesskey`, `cartaction` exposés par Commerce ; `cart_action.php` reste souverain.
- Preview administrateur sans paiement. L'option cadeau est **désactivée** jusqu’à validation d’un véritable parcours cadeau.
- Le showroom seul **n’ouvre pas** les cases du CampusReader ; la disponibilité des contes doit être contrôlée par le serveur du lecteur.

## Médias absents dans l'export Lovable

Les 18 fichiers `src/assets/story-cover-{7..24}.webp` et `src/assets/histoire-1-preview.mp3` ne sont **pas inclus**, seulement leurs fichiers `.asset.json`. Il faut copier les **vrais binaires** dans `src/assets/` avant une mise en vente. L’absence d’une couverture affiche un fond uni et le lecteur audio est désactivé pour ne pas afficher une erreur/broken URL.

## Compilation (poste de travail avec npm et accès aux dépendances)

```bash
cd /tmp/CampusFR_Avent_Bridge_source_DEV_v2
python3 scripts/audit_source.py
bash scripts/build-dev-isolated.sh
```

**Correction v2** : Vite `6.4.5` + `@vitejs/plugin-react` `4.7.0`, compatibles avec Node.js `22.11.0` et `@tailwindcss/vite` `4.2.1`. Les versions précédentes (Vite `8.1.5` et React Plugin `5.2.0`) causaient un conflit `ERESOLVE`, et la branche Vite 7 ne convient pas à Node 22.11. Ne pas employer `npm --force` ou `--legacy-peer-deps`. Le script construit une copie temporaire, en tant que `nobody` lorsqu'il est lancé par root, puis imprime le chemin du build.


Ce dépôt livre **le code source**, pas un artefact `dist/` : les registres npm ne sont pas accessibles dans l'environnement de préparation et la compilation finale n'a donc pas pu être exécutée ici. Le `package.json` contient des versions explicites et les importations Vite ciblent le seul `src/bridge/entry.tsx`. À la fin du build, vérifier les sorties `dist/main.js`, `dist/style.css` et les fichiers `dist/assets` (références `new URL(..., import.meta.url)` attendues par le gestionnaire d’aperçu).

## Intégration sur DEV seulement, après revue

1. Sur DEV, installer séparément le petit patch PHP du ZIP `CampusFR_Avent_patch_Moodle_DEV.zip` en faisant d'abord `bash deploy-dev.sh check`, puis seulement après approbation `bash deploy-dev.sh apply`. Il n'agit que sur `/var/www/moodle-dev/local/subscriptions/showroom.php` et ajoute l’identifiant `advent_calendar_2026` à la liste des designs qui utilisent le footer natif fiable. Le script s'arrête si le fichier actuel diverge du ZIP d’origine (SHA-256 contrôlé).
2. Copier le dossier compilé contenant `manifest.json`, `content/`, `dist/` sur le serveur comme workspace hors webroot, puis utiliser les commandes déjà présentes dans le plugin :

```bash
python3 /var/www/moodle-dev/local/subscriptions/cli/bridge_lovable.py pack /chemin/vers/design-compile /tmp/CampusFR_Avent_DesignPack.zip
sha256sum /tmp/CampusFR_Avent_DesignPack.zip
# Vérifier le contenu et le SHA-256 du pack avant l'approbation suivante.
sudo python3 /var/www/moodle-dev/local/subscriptions/cli/bridge_lovable.py install-dev /tmp/CampusFR_Avent_DesignPack.zip --approve-sha256 <SHA256_VERIFIE>
```

3. Dans le **CRM Showrooms** de DEV, créer une NOUVELLE fiche `draft`, utiliser le template Bridge, choisir le design `advent_calendar_2026`, remplir les slugs FR/EN/RU, rattacher le **vrai SKU du Calendrier** et son rôle Commerce `calendar` (ou un seul rôle non-gift, non-donation), et vérifier les prix les devises autorisées par Commerce (actuellement EUR/RUB). **Ne pas réutiliser la fiche Articles ni son identifiant #8.**
4. Tester en aperçu admin, ensuite avec des comptes de test et paiements **Stripe test** lorsque le produit et les accès CampusReader sont prêts. Ne publier la fiche DEV qu’après réussite de cette recette ; **PROD nécessite une validation séparée explicite.**
5. Purger les caches Moodle DEV après installation du design compilé :

```bash
sudo -u www-data /usr/bin/php8.4 /var/www/moodle-dev/admin/cli/purge_caches.php
```

Ajuster le chemin PHP au binaire PHP **8.4.27** réellement présent sur le serveur avant exécution ; le script de patch vérifie lui-même la version requise. Ne jamais exécuter de ces commandes sur PROD.

## Recette à exécuter sur DEV avant l’ouverture des ventes

- `[ ]` Installation isolée, Articles/Alf toujours fonctionnel, aucune modification PROD.
- `[ ]` FR/EN/RU sur les 24 portes, FAQ, boutons, offres, descriptions, messages d’erreur, liens légaux.
- `[ ]` EUR → RUB → EUR sans rechargement ; les **deux** prix, `priceid` et footer/mentions et liens légaux suivent la réponse serveur ; aucun prix codé en dur.
- `[ ]` Modification de devise échouée : ancien prix/ancien footer conservés, erreur traduite ; aucun checkout avec prix incohérent.
- `[ ]` Ancien/jeune navigateur mobile, petites résolutions, contrastes en Dark Mode, navigation clavier/lecteur d’écran.
- `[ ]` 24 couvertures et extrait audio réels présents, lecture opérationnelle, pas d’erreur de réseau.
- `[ ]` Avant 01/12/2026 00:00 Europe/Paris, 24 cases du vrai Reader verrouillées malgré accès direct ou contournement du navigateur ; ouverture progressive côté serveur.
- `[ ]` Checkout test Stripe EUR/RUB, autorisations et accès après achat, retours/annulation, double clic, reprise du panier ; aucune exposition d’accès en preview.
- `[ ]` Le vrai parcours cadeau est implémenté avant activation du bouton cadeau ; sinon le désactiver comme dans ce prototype.
- `[ ]` Traduction des e-mails de vente/Reader et routes de retour, pas seulement la vitrine.

### Limites de ce livrable

Les commandes sont **fournies**, pas exécutées sur `/var/www/moodle-dev` dans cet environnement. Aucun moyen d’affirmer que le ZIP source est installé, compilé, fonctionnel dans le navigateur réel ou Stripe E2E validé. L’envoi par e-mail d’un calendrier cadeau et les fichiers médias manquants restent à compléter.


## X3 — finition visuelle et éditoriale (22 novembre 2026)

- Correction des couleurs et bordures dans le **Shadow DOM** par déclaration des tokens sur `:host` (dans la version précédente elles étaient déclarées seulement sur `:root`, hors du composant). Aucun style global Moodle n'est modifié.
- La langue de l'interface reste FR/EN/RU, mais **24 titres d'histoires + 5 textes manuscrits** restent volontairement français dans toutes les langues.
- Séparateurs FAQ : traits pleins, couleurs héritées du design.
- Médias (covers 7–24 et MP3 de démonstration) **non inclus dans l'export Lovable**. Ils se configurent sur DEV avec le patch Moodle `CampusFR_Avent_CRM_Medias_Contacts_DEV_v3.zip`, qui offre un upload Moodle (fichier audio, 24 images) et 2 liens de contact. Ne pas inventer des visuels.
- Le cadeau conserve son statut « prochainement » ; aucun checkout cadeau n'est branché.
- Faire un nouveau `pack`, contrôler SHA-256, installer la version 0.2.0 avec `install-dev ... --replace` **après revue** et sauvegarde de la version en place.
