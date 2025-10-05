<?php
require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/_template.php');

\local_subscriptions\subscription_config::guard_public_access();

$PAGE->set_url(new moodle_url('/local/subscriptions/pages/terms_fr.php'));

$title = 'Conditions Générales (CGU/CGV) — CampusFR';
$body = '
<h2>Objet</h2>
<p>Ces conditions régissent l’accès à la plateforme CampusFR et l’achat d’abonnements.</p>

<h2>Abonnements</h2>
<p>Durée, prix et contenu sont indiqués sur la page d’offre. Accès actif pendant la période payée.</p>

<h2>Paiement</h2>
<p>Paiement sécurisé via Stripe et/ou Alfa-Bank. Les taxes applicables sont précisées lors du paiement.</p>

<h2>Rétractation & remboursements</h2>
<p>Selon la loi applicable et nos politiques internes. Pour toute demande : support@campusfr.fr.</p>

<h2>Compte & usage</h2>
<p>Accès personnel. Il est interdit de partager les identifiants et de redistribuer les contenus.</p>

<h2>Responsabilité</h2>
<p>La plateforme est fournie « en l’état ». Nous mettons en œuvre des moyens raisonnables de disponibilité.</p>

<h2>Évolution</h2>
<p>Les présentes conditions peuvent évoluer. La version en ligne fait foi.</p>

<h2>Droit applicable</h2>
<p>À préciser selon votre politique (pays/tribunal compétent).</p>
';
ls_simple_page($title, $body);
