# CampusFR Advent – V3, recette DEV

## Différences visuelles corrigées

- Variables de design réinitialisées à l’intérieur du Shadow DOM (`:host`), pour retrouver les bordures dorées / roses, les cartes et la FAQ de Lovable.
- Bord supérieur et ombre de l’hôte supprimés côté Design Bridge (si une ligne extérieure au composant persiste, inspecter l’encadrement Moodle et ne pas modifier globalement les showrooms).
- Signatures manuscrites en français invariables, quelle que soit la locale FR/EN/RU ; 24 titres des histoires toujours en FR.
- Traits FAQ pleins.
- Boutons Telegram/WhatsApp affichés uniquement avec une URL officielle configurée côté CRM.

## Médias

Les 18 couvertures manquantes et l’extrait audio ne sont PAS présents dans l’export Lovable (seulement `.asset.json`). Le patch CRM V3 fournit des champs d’upload pour les 24 cases et l’extrait MP3, avec stockage dans Moodle File API et URL fournie au Bridge au rendu. **L’absence de média n’est pas un bug TypeScript.**

## Construction (sur serveur DEV, sans modifier Moodle)

```bash
cd /tmp
unzip -oq CampusFR_Avent_Bridge_source_DEV_v3.zip
cd /tmp/CampusFR_Avent_Bridge_source_DEV_v3
python3 scripts/audit_source.py
bash scripts/build-dev-isolated.sh
```

Récupérer le `DOSSIER_BUILD` dans le terminal. Puis construire et valider le Design Pack (adapter `BUILD`) :

```bash
BUILD=/tmp/campusfr-advent-build.XXXXXXXX
CLI=/var/www/moodle-dev/local/subscriptions/cli/bridge_lovable.py
STAGE=$(mktemp -d /tmp/campusfr-advent-v3-pack.XXXXXXXX)
mkdir -p "$STAGE/source/content"
cp "$BUILD/manifest.json" "$STAGE/source/"
cp "$BUILD/content/"{fr,en,ru}.json "$STAGE/source/content/"
cp -a "$BUILD/dist" "$STAGE/source/"
python3 "$CLI" pack "$STAGE/source" "$STAGE/CampusFR_Avent_DesignPack_v3.zip"
unzip -t "$STAGE/CampusFR_Avent_DesignPack_v3.zip" | tail -n 1
sha256sum "$STAGE/CampusFR_Avent_DesignPack_v3.zip"
```

### Réinstallation du design (après lecture et approbation du SHA affiché)

La commande `install-dev` exige `--replace`, car l’ancienne version 0.1.0 est déjà présente ; elle réalise une sauvegarde de l’ancien design. **Ne pas sauter la vérification du SHA**. La purge Moodle DEV est à effectuer ensuite avec `www-data` et le PHP fonctionnel du serveur.

## Patch Moodle séparé

Installer en priorité `CampusFR_Avent_CRM_Medias_Contacts_DEV_v3.zip` via `bash deploy-dev.sh check` puis `apply` avant de tester les URLs de média. Ce patch vise uniquement `/var/www/moodle-dev` ; il ne modifie ni le design Articles ni le checkout. PROD doit rester intact.

Le parcours « offrir à un ami » n’est pas raccordé : il faudra un projet spécifique cadeau et ses tests de bout en bout avant activation.
