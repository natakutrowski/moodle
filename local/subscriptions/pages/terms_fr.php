<?php
require_once(__DIR__.'/../../../config.php');
require_once(__DIR__.'/_template.php');
\local_subscriptions\subscription_config::guard_public_access();
$PAGE->set_url(new moodle_url('/local/subscriptions/pages/terms_fr.php'));
$title = 'Conditions générales d’utilisation et de vente — CampusFR';
$body = '
<p><strong>Version 2026-09-v1.</strong></p>
<h2>1. Objet</h2><p>Les présentes conditions encadrent l’utilisation de CampusFR et l’achat de produits et services pédagogiques numériques proposés sur la plateforme.</p>
<h2>2. Vendeur</h2><p>L’entité juridique vendeuse applicable à votre achat est celle identifiée au checkout puis sur votre facture ou reçu. CampusFR peut exploiter plusieurs entités selon le marché concerné.</p>
<h2>3. Offre et commande</h2><p>Les caractéristiques essentielles, le prix, la devise, la durée ou la nature de l’accès et les éventuelles conditions particulières figurent sur la page de l’offre avant la commande. Une commande n’est définitive qu’après validation du paiement lorsqu’un paiement est requis.</p>
<h2>4. Prix, taxes et paiement</h2><p>Les prix sont affichés dans la devise sélectionnée. Les taxes applicables sont présentées lorsque requises. Les paiements peuvent être traités par des prestataires tels que Stripe, PayPal ou Alfa-Bank selon le marché et le moyen de paiement disponible. CampusFR ne stocke pas les données complètes de carte bancaire.</p>
<h2>5. Accès numérique</h2><p>Lorsque l’offre prévoit un accès immédiat, celui-ci est activé après confirmation du paiement ou selon les conditions particulières de l’offre. L’accès est personnel et ne peut pas être revendu, partagé ou redistribué.</p>
<h2>6. Droit de rétractation et remboursements</h2><p>Les droits légaux de rétractation applicables au consommateur sont respectés. Pour certains contenus ou services numériques, la loi peut prévoir une perte du droit de rétractation lorsque l’exécution commence avant la fin du délai légal, mais uniquement si les conditions légales et les consentements exprès requis sont réunis. Les remboursements commerciaux éventuels s’ajoutent aux droits légaux et ne les remplacent pas.</p>
<h2>7. Compte utilisateur</h2><p>L’utilisateur doit fournir des informations exactes et protéger ses identifiants. Toute utilisation abusive, frauduleuse ou portant atteinte aux contenus ou aux autres utilisateurs peut entraîner une suspension de l’accès dans les limites permises par la loi.</p>
<h2>8. Propriété intellectuelle</h2><p>Les cours, exercices, vidéos, fichiers, textes, marques, visuels et autres contenus de CampusFR sont protégés. L’achat confère uniquement le droit d’usage prévu par l’offre et n’emporte aucun transfert de propriété intellectuelle.</p>
<h2>9. Disponibilité du service</h2><p>CampusFR met en œuvre des moyens raisonnables pour assurer la disponibilité de la plateforme. Des interruptions peuvent toutefois survenir pour maintenance, sécurité ou raisons techniques.</p>
<h2>10. Offres personnelles</h2><p>Une offre personnelle peut être réservée à un bénéficiaire déterminé et assortie de conditions de prix, de durée ou d’éligibilité spécifiques. Ces conditions complètent les présentes conditions générales.</p>
<h2>11. Données personnelles</h2><p>Le traitement des données personnelles est décrit dans la Politique de confidentialité applicable.</p>
<h2>12. Support et réclamations</h2><p>Pour toute question relative à une commande, un paiement ou un accès, contactez support@campusfr.fr. Les droits impératifs du consommateur dans son pays de résidence restent applicables lorsqu’ils ne peuvent être écartés contractuellement.</p>
<h2>13. Évolution des conditions</h2><p>Les conditions peuvent évoluer. La version acceptée lors d’une commande est enregistrée avec cette commande et reste la référence pour celle-ci.</p>';
ls_simple_page($title, $body);
