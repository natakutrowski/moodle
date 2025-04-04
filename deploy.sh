#!/bin/bash

# ============ CONFIGURATION ============

REMOTE_USER="zvgrfmv"
REMOTE_HOST="ssh.cluster100.hosting.ovh.net"
REMOTE_DIR="/home/zvgrfmv/www/moodle"
REMOTE_MOODLEDATA_DIR="/home/zvgrfmv/moodledata"
LOG_DIR="/Applications/MAMP/logs"
LOCAL_MOODLEDATA_DIR="/Applications/MAMP/moodledata"
BRANCH="campusFR"

LOCAL_MOODLE_DIR="/Applications/MAMP/htdocs/moodle"
LOCAL_SQL_DUMP="${LOCAL_MOODLE_DIR}/moodle.sql"
DB_LOCAL_NAME="moodle"  # Nom de ta base de données locale
DB_LOCAL_USER="root"     # Nom d'utilisateur local MySQL (MAMP)
DB_LOCAL_PASS="root"     # Mot de passe local MySQL (MAMP)
DB_OVH_HOST="nk71665-001.eu.clouddb.ovh.net"
DB_OVH_PORT="35922"
DB_OVH_NAME="moodle"   # Nom de ta base de données OVH
DB_OVH_USER="natakutrowski"   # Nom d'utilisateur MySQL sur OVH
DB_OVH_PASS="Nath2210"         # Mot de passe MySQL sur OVH

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
LOG_FILE="${LOG_DIR}/deploy_${TIMESTAMP}.log"

# ============ DEPLOIEMENT ============

echo "Déploiement en cours..." | tee -a $LOG_FILE

# 1. Exporter la base de données locale
echo "Exportation de la base de données locale..." | tee -a $LOG_FILE
mysqldump -u $DB_LOCAL_USER -p$DB_LOCAL_PASS $DB_LOCAL_NAME > $LOCAL_SQL_DUMP 2>> $LOG_FILE

# 2. Push local vers GitHub
if [ -z "$1" ]; then
  echo "Erreur : Vous devez spécifier un message de commit." | tee -a $LOG_FILE
  exit 1
fi

echo "Pushing local changes to GitHub..." | tee -a $LOG_FILE
git add .
git commit -m "$1" 2>&1 | tee -a $LOG_FILE
git push origin $BRANCH 2>&1 | tee -a $LOG_FILE

# 2. Vérification de la connexion SSH
if ssh -q -o BatchMode=yes ${REMOTE_USER}@${REMOTE_HOST} -p 22 exit; then
  echo "Connexion SSH réussie." | tee -a $LOG_FILE
else
  echo "Erreur : Impossible de se connecter au serveur OVH via SSH." | tee -a $LOG_FILE
  exit 1
fi

# 3. Transférer la base de données vers OVH
echo "Transfert de la base de données vers OVH..." | tee -a $LOG_FILE
scp $LOCAL_SQL_DUMP ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_DIR} 2>> $LOG_FILE

# 4. Connexion SSH et importation sur OVH
echo "Importation de la base de données sur OVH..." | tee -a $LOG_FILE
ssh ${REMOTE_USER}@${REMOTE_HOST} "mysql -h ${DB_OVH_HOST} -P ${DB_OVH_PORT} -u ${DB_OVH_USER} -p${DB_OVH_PASS} ${DB_OVH_NAME} < ${REMOTE_DIR}/moodle.sql" 2>> $LOG_FILE

# 5. Suppression du fichier SQL sur OVH (optionnel)
echo "Suppression du fichier SQL sur OVH..." | tee -a $LOG_FILE
ssh ${REMOTE_USER}@${REMOTE_HOST} "rm ${REMOTE_DIR}/moodle.sql" 2>> $LOG_FILE

# 6. Mise à jour du code sur OVH
echo "Pulling latest changes from GitHub on OVH..." | tee -a $LOG_FILE
ssh ${REMOTE_USER}@${REMOTE_HOST} -p 22 "cd ${REMOTE_DIR} && git pull origin ${BRANCH}" 2>&1 | tee -a $LOG_FILE

# 7. Synchronisation des fichiers moodledata avec rsync
echo "Synchronisation de moodledata..." | tee -a $LOG_FILE
rsync -avz --progress --delete --exclude="config.php" ${LOCAL_MOODLEDATA_DIR}/ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_MOODLEDATA_DIR} 2>&1 | tee -a $LOG_FILE

# 5. Message de confirmation

echo "Déploiement terminé avec succès !" | tee -a $LOG_FILE

echo "Résumé :"
echo "- Code déployé sur ${REMOTE_HOST} dans ${REMOTE_DIR}" | tee -a $LOG_FILE
echo "- Base de données ${DB_OVH_NAME} mise à jour" | tee -a $LOG_FILE
echo "- moodledata synchronisé avec rsync" | tee -a $LOG_FILE
echo "- Logs disponibles dans ${LOG_FILE}" | tee -a $LOG_FILE
