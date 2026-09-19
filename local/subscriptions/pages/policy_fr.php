<?php
require_once(__DIR__.'/../../../config.php'); require_once(__DIR__.'/_template.php');
\local_subscriptions\subscription_config::guard_public_access();
$PAGE->set_url(new moodle_url('/local/subscriptions/pages/policy_fr.php'));
$title='Politique de confidentialité — CampusFR';
$body='<p><strong>Version 2026-09-v1.</strong></p>
<h2>1. Responsable du traitement</h2><p>L’entité CampusFR responsable de la relation avec vous, telle qu’identifiée au checkout, sur votre commande ou sur votre facture, traite les données nécessaires à cette relation. Contact général : admin@campusfr.fr.</p>
<h2>2. Données traitées</h2><p>Nous pouvons traiter les données de compte et d’identité, coordonnées, langue, pays ou marché, achats et accès, historique de paiement et de remboursement, échanges avec le support, données techniques et de sécurité, ainsi que les consentements et preuves contractuelles nécessaires.</p>
<h2>3. Finalités et bases juridiques</h2><p>Les traitements servent notamment à créer et administrer le compte, fournir les contenus, exécuter les commandes, facturer, traiter les paiements et remboursements, assurer le support, sécuriser la plateforme, prévenir la fraude, respecter les obligations légales et, lorsqu’un consentement est requis, gérer les communications facultatives.</p>
<h2>4. Paiements</h2><p>Les prestataires de paiement reçoivent les données nécessaires au paiement. CampusFR ne stocke pas les données complètes de carte bancaire. Selon le moyen choisi, Stripe, PayPal, Alfa-Bank ou un autre prestataire disponible peut intervenir.</p>
<h2>5. Destinataires et sous-traitants</h2><p>Les données peuvent être accessibles aux prestataires strictement nécessaires au fonctionnement de CampusFR : hébergement, infrastructure Moodle, messagerie, support, sécurité, paiement et outils techniques activés par l’exploitant.</p>
<h2>6. Transferts internationaux</h2><p>Certains prestataires peuvent traiter des données dans différents pays. Lorsque la réglementation applicable l’exige, les transferts sont encadrés par les mécanismes juridiques appropriés.</p>
<h2>7. Conservation</h2><p>Les données sont conservées pendant la durée nécessaire aux finalités concernées, à l’exécution de la relation contractuelle, à la sécurité et aux obligations légales, comptables ou probatoires applicables.</p>
<h2>8. Vos droits</h2><p>Selon la réglementation applicable, vous pouvez disposer notamment de droits d’accès, rectification, effacement, limitation, opposition et portabilité, ainsi que du droit de retirer un consentement lorsqu’il constitue la base du traitement. Contact : admin@campusfr.fr. Les personnes relevant du RGPD peuvent également saisir l’autorité de contrôle compétente.</p>
<h2>9. Cookies et données techniques</h2><p>CampusFR utilise les cookies et stockages techniques nécessaires à la session, l’authentification, la sécurité et le fonctionnement du service. Les outils facultatifs nécessitant un consentement sont soumis aux règles applicables.</p>
<h2>10. Sécurité</h2><p>Des mesures techniques et organisationnelles raisonnables sont mises en œuvre pour protéger les données contre l’accès, la perte, l’altération ou la divulgation non autorisés.</p>
<h2>11. Mise à jour</h2><p>Cette politique peut évoluer. Une version stable est associée aux documents présentés lors du checkout afin d’assurer la traçabilité contractuelle.</p>';
ls_simple_page($title,$body);
