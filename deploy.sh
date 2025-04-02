#!/bin/bash

# ============ CONFIGURATION ============

REMOTE_USER="zvgrfmv"
REMOTE_HOST="ssh.cluster100.hosting.ovh.net"
REMOTE_DIR="/home/zvgrfmv/www/moodle"
REMOTE_MOODLEDATA_DIR="/home/zvgrfmv/www/moodledata"
LOG_DIR="/Applications/MAMP/logs"
LOCAL_MOODLEDATA_DIR="/Applications/MAMP/moodledata"
BRANCH="campusFR"

TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
LOG_FILE="${LOG_DIR}/deploy_${TIMESTAMP}.log"

# ============ DEPLOIEMENT ============

echo "Déploiement en cours..." | tee -a $LOG_FILE

# 1. Push local vers GitHub
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

# 3. Mise à jour du code sur OVH
echo "Pulling latest changes from GitHub on OVH..." | tee -a $LOG_FILE
ssh ${REMOTE_USER}@${REMOTE_HOST} "cd ${REMOTE_DIR} && git pull origin ${BRANCH}" 2>&1 | tee -a $LOG_FILE

# 4. Synchronisation des fichiers moodledata avec rsync
echo "Synchronisation de moodledata..." | tee -a $LOG_FILE
rsync -avz --progress --delete --exclude="config.php" ${LOCAL_MOODLEDATA_DIR}/ ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_MOODLEDATA_DIR} 2>&1 | tee -a $LOG_FILE

# 5. Message de confirmation

echo "Déploiement terminé avec succès !" | tee -a $LOG_FILE

echo "Résumé :"
echo "- Code déployé sur ${REMOTE_HOST} dans ${REMOTE_DIR}" | tee -a $LOG_FILE
echo "- moodledata synchronisé avec rsync" | tee -a $LOG_FILE
echo "- Logs disponibles dans ${LOG_FILE}" | tee -a $LOG_FILE
