<?php
namespace local_subscriptions\payment;
defined('MOODLE_INTERNAL') || die();

/**
 * Optionnel: les gateways qui exposent un portail client implémentent cette interface.
 */
interface PortalGatewayInterface extends PaymentGatewayInterface {
    /**
     * Retourne une URL de portail (ou un DTO qui expose getUrl()).
     * $ctx attend : provider_customer_id, subscription_id (si utile), return_url (obligatoire).
     *
     * @return array|object   array['url'=>string] OU un objet { getUrl(): string }
     */
    public function create_portal_session(array $ctx);
}
