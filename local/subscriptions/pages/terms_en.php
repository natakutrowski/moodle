<?php
require_once(__DIR__.'/../../../config.php'); require_once(__DIR__.'/_template.php');
\local_subscriptions\subscription_config::guard_public_access();
$PAGE->set_url(new moodle_url('/local/subscriptions/pages/terms_en.php'));
$title='CampusFR Terms of Use and Sale';
$body='<p><strong>Version 2026-09-v1.</strong></p>
<h2>1. Scope</h2><p>These terms govern use of CampusFR and purchases of digital educational products and services.</p>
<h2>2. Seller</h2><p>The legal entity selling your purchase is identified at checkout and on the related invoice or receipt. CampusFR may operate through different legal entities depending on the relevant market.</p>
<h2>3. Offer and order</h2><p>Essential features, price, currency, access duration or nature, and any specific conditions are shown before purchase. An order becomes final after payment confirmation where payment is required.</p>
<h2>4. Prices, taxes and payment</h2><p>Prices are shown in the selected currency. Applicable taxes are displayed where required. Payments may be processed by providers such as Stripe, PayPal or Alfa-Bank. CampusFR does not store full payment-card details.</p>
<h2>5. Digital access</h2><p>Where immediate access is included, access is activated after payment confirmation or as otherwise stated in the offer. Access is personal and may not be resold, shared or redistributed.</p>
<h2>6. Withdrawal and refunds</h2><p>Applicable statutory consumer withdrawal rights are respected. For some digital content or services, applicable law may allow the withdrawal right to be lost when performance starts early, but only where all required legal conditions and express consents are met. Any commercial refund policy is additional to statutory rights.</p>
<h2>7. Account</h2><p>Users must provide accurate information and protect their credentials. Fraudulent or abusive use may lead to suspension where legally permitted.</p>
<h2>8. Intellectual property</h2><p>CampusFR courses, exercises, videos, files, text, trademarks and visual assets are protected. A purchase grants only the usage rights stated in the offer.</p>
<h2>9. Service availability</h2><p>CampusFR uses reasonable efforts to maintain service availability. Interruptions may occur for maintenance, security or technical reasons.</p>
<h2>10. Personal offers</h2><p>A personal offer may be limited to a named beneficiary and may contain specific price, duration or eligibility terms that supplement these general terms.</p>
<h2>11. Personal data</h2><p>Personal-data processing is described in the applicable Privacy Policy.</p>
<h2>12. Support and complaints</h2><p>For purchase, payment or access questions, contact support@campusfr.fr. Mandatory consumer protections applicable in the customer’s country remain unaffected where they cannot lawfully be excluded.</p>
<h2>13. Changes</h2><p>These terms may change. The version accepted for a purchase is recorded with that purchase and remains the reference version for it.</p>';
ls_simple_page($title,$body);
