<?php

declare(strict_types=1);

namespace local_subscriptions;

use advanced_testcase;
use local_subscriptions\commerce\mail\template\studio\CommerceMailTemplateRepository;

final class commerce_mail_template_repository_test extends advanced_testcase {
    public function test_save_update_and_delete_template(): void {
        global $DB;
        $this->resetAfterTest();
        $repository = new CommerceMailTemplateRepository($DB);
        $created = $repository->save([
            'mailtype' => 'purchase_receipt', 'language' => 'fr', 'enabled' => 1,
            'subject' => 'Merci {firstname}', 'preheader' => 'Commande {order_reference}',
            'heading' => 'Merci !', 'introhtml' => '<p>Bonjour</p>', 'outrohtml' => '<p>À bientôt</p>',
            'signaturehtml' => '<p>CampusFR</p>', 'headerimage' => 0,
        ], 2);
        $this->assertGreaterThan(0, $created->id);
        $this->assertSame('Merci {firstname}', $repository->get('purchase_receipt', 'fr')->subject);
        $updated = $repository->save([
            'mailtype' => 'purchase_receipt', 'language' => 'fr', 'enabled' => 0,
            'subject' => 'Nouveau sujet', 'preheader' => '', 'heading' => '', 'introhtml' => '',
            'outrohtml' => '', 'signaturehtml' => '', 'headerimage' => 1,
        ], 2);
        $this->assertSame($created->id, $updated->id);
        $this->assertSame('0', (string)$updated->enabled);
        $repository->delete('purchase_receipt', 'fr');
        $this->assertNull($repository->get('purchase_receipt', 'fr'));
    }
}
