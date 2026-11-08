# CampusFR X2.3A — moteur de compilation isolée

L'interface CRM ne lance **jamais** `npm` avec les droits PHP/Moodle. Un bouton crée une tâche Moodle, exécutée au prochain passage du cron. La tâche appelle `compile_preview.py`, qui refuse toute exécution rootful et lance le build dans **Podman rootless** uniquement.

## Configuration initiale à effectuer une fois (sur DEV)

Le serveur doit disposer de Podman **rootless** et d'un environnement utilisateur fonctionnel pour l'utilisateur qui exécute les tâches isolées : `www-data` si le cron est root, sinon l'utilisateur du cron (non-root). Ne pas accorder d'accès au socket Docker à `www-data` et ne pas donner de privilège sudo général à ce compte.

Contrôles de diagnostic non destructifs :

```bash
command -v podman
id www-data
runuser -u www-data -- podman info
```

Si ces commandes échouent, le moteur n'est pas prêt. Il faut configurer Podman rootless (`subuid`, `subgid`, `XDG_RUNTIME_DIR`, stockage du compte) avec l'administrateur système. **Ne pas contourner** ce blocage par un `docker` rootful, un `sudo npm`, ou un chmod global de `moodledata`.

L'image `localhost/campusfr-lovable-builder:1` doit être construite à partir des dépendances vérifiées. Exemple depuis le dossier du moteur, **dans l'environnement rootless du compte concerné** :

```bash
podman build --pull=always -f BuilderImage.Dockerfile -t localhost/campusfr-lovable-builder:1 .
podman image exists localhost/campusfr-lovable-builder:1
```

Cette construction initiale télécharge les paquets npm et réchauffe le cache d'installation pour le profil Articles / Alf présent dans `builder-articles-deps.json`. Elle n'exécute pas les scripts npm (`--ignore-scripts`). Le build d'un projet utilise **uniquement** ce cache hors réseau (`npm --offline --ignore-scripts`). Un autre projet avec des dépendances absentes échouera proprement ; il faudra réviser ses dépendances et mettre à jour l'image avec un nouvel ID/version. Le code source du showroom n'entre pas dans l'image de base.

## Séparation de sécurité

- Le navigateur ne transmet jamais de commande ni de chemin arbitraire.
- Les ZIP préparés sont revalidés avant extraction ; liens symboliques et traversées de répertoires interdits.
- Podman `--network=none`, `--pull=never`, `--read-only`, `--cap-drop=ALL`, `--security-opt=no-new-privileges`, mémoire 2 Go, deux CPU, 128 processus, délai 150–180 s.
- Les seuls volumes accessibles au conteneur sont le dossier de préparation éphémère, le dossier de résultats et le script de build **en lecture seule**.
- Installation de dépendances en mode hors-ligne et sans scripts de cycle de vie npm.
- Le résultat reste dans Moodle **temporaire**, en archive `draft/...` marquée NON_PUBLIABLE ; aucune écriture dans `designs/`, aucune création de showroom, aucun moyen de paiement activé.
- Le journal est limité à 30 Ko côté CRM. Le ZIP source et le résultat sont conservés au plus deux heures dans le flux utilisateur existant.

**Le succès d'une compilation ne signifie pas équivalence Articles / Alf.** Il reste à raccorder Commerce, compléter toutes les traductions, revoir les 17 attributs et les textes dynamiques, puis comparer la page reconstruite visuellement.
