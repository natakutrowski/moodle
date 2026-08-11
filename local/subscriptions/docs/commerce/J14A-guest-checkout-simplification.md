# J14A — Simplification du Guest Checkout

## Parcours cible

- Achat direct : Showroom/Boutique → Checkout unifié → Provider → Confirmation.
- Achat via panier : Panier → Checkout unifié → Provider → Confirmation.
- Les coordonnées invité (email, prénom, nom) sont collectées dans le Checkout unifié.
- Pour un utilisateur connecté, aucun champ d'identité n'est rendu.
- Le type de parcours (`direct` ou `cart`) est persisté dans la commande et réutilisé dans `order_result.php`.

## Compatibilité

`guest_checkout.php` est conservé comme route de compatibilité et redirige vers `commerce_checkout.php`. Il ne faut pas le supprimer tant que des URLs externes, favoris ou anciens e-mails peuvent encore le référencer.

## Nettoyage

### Supprimable après validation complète et une période de transition

- `templates/checkout/guest_identity.mustache` : plus aucun flux applicatif ne le rend après J14A.

### À conserver

- `guest_checkout.php` : redirection de compatibilité.
- `guest_checkout_resume.php` : reprise après connexion d'un compte existant.
- `amd/src/guest_checkout_security.js` et son build : validation live du formulaire intégré et modale post-paiement.
- les services `CommerceGuestCheckout*` : provisionnement, transfert de panier, activation et reprise.
