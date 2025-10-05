<?php
require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/_template.php');

\local_subscriptions\subscription_config::guard_public_access();

$PAGE->set_url(new moodle_url('/local/subscriptions/pages/policy_fr.php'));

$title = 'Politique de confidentialité — CampusFR';
$body = '
<h2>1. Responsable du traitement</h2>
<p>CampusFR, contact : admin@campusfr.fr.</p>

<h2>2. Données collectées</h2>
<p>Compte utilisateur (nom, prénom, e-mail), données de paiement (traitées par nos prestataires),
journal technique (IP, agent). Nous ne stockons pas les données complètes de carte.</p>

<h2>3. Finalités</h2>
<p>Création du compte, accès aux cours, facturation, support, sécurité et prévention de fraude.</p>

<h2>4. Base légale</h2>
<p>Exécution du contrat, intérêt légitime (sécurité/service), obligations légales.</p>

<h2>5. Sous-traitants</h2>
<p>Fournisseurs de paiement (Stripe / Alfa-Bank), hébergement, e-mail. Les transferts sont limités au nécessaire.</p>

<h2>6. Durée</h2>
<p>Compte: durée d’usage + rétention légale. Journaux techniques: durée courte (sécurité).</p>

<h2>7. Droits</h2>
<p>Accès, rectification, effacement, portabilité, opposition, limitation. Contact: admin@campusfr.fr.</p>

<h2>8. Cookies</h2>
<p>Techniques (session) + mesure d’audience si activée. Vos choix peuvent être gérés dans le navigateur.</p>

<h2>9. Contact</h2>
<p>Pour toute question : admin@campusfr.fr.</p>
';
ls_simple_page($title, $body);
