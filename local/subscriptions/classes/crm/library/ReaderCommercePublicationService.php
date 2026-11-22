<?php
// CampusFR D2.4.3 — Editorial publication and Native Commerce binding.
declare(strict_types=1);
namespace local_subscriptions\crm\library;
defined('MOODLE_INTERNAL') || die();

use local_campusreader\local\calendar_policy;
use local_campusreader\local\publication_access;
use local_subscriptions\commerce\catalog\domain\CommerceProductEntitlementDefinition;
use local_subscriptions\commerce\catalog\service\CommerceCatalogFactory;
use local_subscriptions\commerce\catalog\admin\CommerceProductLifecycleService;

final class ReaderCommercePublicationService {
    public const TABLE = 'local_cr_publication';
    public static function ready(): bool {
        global $DB;
        return $DB->get_manager()->table_exists(new \xmldb_table(self::TABLE));
    }
    public static function list_all(): array {
        global $DB;
        return array_values($DB->get_records(self::TABLE, [], 'id DESC', '*', 0, 200));
    }
    public static function get(int $id): \stdClass {
        global $DB;
        return $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
    }
    public static function key(string $slug): string { return publication_access::key($slug); }
    public static function approved_revisions(): array {
        global $DB;
        return array_values($DB->get_records('local_cr_anthrev', ['state' => 'approved'], 'id DESC',
            'id,anthologyid,revno,digest', 0, 200));
    }
    /** Only propose fresh, non-sale, unbound Service products in the CRM. */
    public static function candidate_products(): array {
        global $DB;
        $used = [];
        foreach ($DB->get_records_select(self::TABLE, 'productsku IS NOT NULL', [], '', 'id,productsku') as $pub) {
            $used[strtoupper(trim((string)$pub->productsku))] = true;
        }
        $result = [];
        foreach ($DB->get_records('local_subs_commerce_product',
            ['type' => 'service'], 'name ASC', 'id,sku,name,status', 0, 500) as $product) {
            $sku = strtoupper(trim((string)$product->sku));
            if (!self::eligible_product_status((string)$product->status) || isset($used[$sku]) ||
                    $DB->record_exists('local_subs_commerce_prod_ent', ['productid' => (int)$product->id]) ||
                    $DB->record_exists('local_subs_commerce_grant', ['productsku' => $sku])) {
                continue;
            }
            $result[] = $product;
        }
        return $result;
    }
    /** The standard Native product editor creates inactive, not draft, products. */
    public static function eligible_product_status(string $status): bool {
        return in_array($status, ['draft', 'inactive'], true);
    }
    private static function assert_product_unused(int $productid, string $sku): void {
        global $DB;
        $counts = (new CommerceProductLifecycleService($DB))->dependency_counts($productid, $sku);
        foreach (['nativepurchaseitems', 'nativepurchases', 'legacyplansales',
            'legacydigitalsales', 'grants'] as $key) {
            if (($counts[$key] ?? 0) > 0) {
                throw new \invalid_parameter_exception(
                    'Ce produit a déjà été acheté ou a accordé des droits. Choisir un produit QA neuf.');
            }
        }
    }
    public static function collections(): array {
        global $DB;
        return array_values($DB->get_records('local_cr_collection', [], 'title ASC', 'id,title,slug', 0, 200));
    }
    private static function approved(int $anthologyid, int $revisionid): array {
        $rev = ReaderAnthologyRevisionService::get($anthologyid, $revisionid);
        if ((string)$rev->state !== 'approved') {
            throw new \invalid_parameter_exception('La version doit être validée avant publication.');
        }
        $snapshot = ReaderAnthologyRevisionService::snapshot($anthologyid, $revisionid);
        ReaderAnthologyRevisionService::assert_media($revisionid, $snapshot);
        if (count($snapshot['chapters'] ?? []) < 1 || count($snapshot['chapters']) > 100) {
            throw new \invalid_parameter_exception('Le recueil validé est vide ou trop long.');
        }
        return $snapshot;
    }
    /** Freeze every calendar date and book→chapter mapping at publication time, independent of live edits. */
    public static function frozen_calendar(int $collectionid, array $snapshot): string {
        global $DB;
        $collection = $DB->get_record('local_cr_collection', ['id' => $collectionid], '*', MUST_EXIST);
        $count = calendar_policy::active_count($collection);
        if ($count < 1 || $count > 31) {
            throw new \invalid_parameter_exception('Nombre de cases non valide.');
        }
        $slots = array_values($DB->get_records('local_cr_slot',
            ['collectionid' => $collectionid], 'position ASC', '*', 0, 32));
        $chapters = [];
        foreach ($snapshot['chapters'] as $ch) {
            $bid = (int)($ch['bookid'] ?? 0);
            if ($bid <= 0 || isset($chapters[$bid])) {
                throw new \invalid_parameter_exception('Histoires du recueil ambiguës.');
            }
            $chapters[$bid] = (int)$ch['position'];
        }
        $out = [];
        $seen = [];
        $last = 0;
        foreach ($slots as $slot) {
            $position = (int)$slot->position;
            if ($position > $count) { continue; }
            $bid = (int)($slot->bookid ?? 0);
            $timestamp = (int)$slot->unlockat;
            if ($position !== count($out) + 1 || $bid < 1 || !isset($chapters[$bid]) ||
                    isset($seen[$bid]) || $timestamp <= $last) {
                throw new \invalid_parameter_exception('Les cases actives doivent avoir une histoire du recueil et des dates croissantes.');
            }
            $seen[$bid] = true;
            $last = $timestamp;
            $out[] = ['position' => $position, 'chapter' => $chapters[$bid],
                'unlockat' => $timestamp, 'teaser' => (string)$slot->teaser,
                'layoutsize' => (string)$slot->layoutsize];
        }
        if (count($out) !== $count || count($chapters) !== $count ||
                (int)$collection->libraryafter <= $last) {
            throw new \invalid_parameter_exception('Le calendrier et le recueil doivent contenir exactement les mêmes histoires et une date de bascule cohérente.');
        }
        return json_encode(['schema' => 1, 'timezone' => 'Europe/Paris',
            'releasepolicy' => 'chapter-ready-v1',
            'libraryafter' => (int)$collection->libraryafter, 'slots' => $out],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
    /** Insert a closed editorial edition. Does not activate sales or public routes. */
    public static function create(string $slug, string $kind, int $anthologyid,
        int $revisionid, int $collectionid): int {
        global $DB;
        self::key($slug);
        if (!in_array($kind, ['anthology', 'calendar'], true)) {
            throw new \invalid_parameter_exception('Type de publication inconnu.');
        }
        $data = self::approved($anthologyid, $revisionid);
        $calendar = $kind === 'calendar' ? self::frozen_calendar($collectionid, $data) : '';
        if ($kind === 'anthology') { $collectionid = 0; }
        if ($DB->record_exists(self::TABLE, ['slug' => $slug])) {
            throw new \invalid_parameter_exception('Ce slug est déjà utilisé.');
        }
        return (int)$DB->insert_record(self::TABLE, (object)[
            'slug' => $slug, 'kind' => $kind, 'anthologyid' => $anthologyid,
            'revisionid' => $revisionid, 'collectionid' => $collectionid,
            'calendarjson' => $calendar, 'productsku' => null, 'status' => 'draft',
            'timecreated' => time(), 'timemodified' => time(), 'publishedat' => 0,
        ]);
    }
    public static function change_revision(int $id, int $rev, int $collectionid): void {
        global $DB;
        $pub = self::get($id);
        if ((string)$pub->status !== 'draft') {
            throw new \invalid_parameter_exception('Mettre en brouillon avant de changer la version.');
        }
        $data = self::approved((int)$pub->anthologyid, $rev);
        $calendar = $pub->kind === 'calendar' ? self::frozen_calendar($collectionid, $data) : '';
        // Preserve localized visual labels when refreshing a DRAFT editorial revision.
        // All frozen dates and chapter bindings still come solely from frozen_calendar().
        if ($pub->kind === 'calendar') {
            $previous = json_decode((string)$pub->calendarjson, true);
            if (is_array($previous) && isset($previous['display']) && is_array($previous['display'])) {
                $next = json_decode($calendar, true, 512, JSON_THROW_ON_ERROR);
                $next['display'] = $previous['display'];
                $calendar = json_encode($next, JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            }
        }
        $DB->update_record(self::TABLE, (object)[
            'id' => $id, 'revisionid' => $rev,
            'collectionid' => $pub->kind === 'calendar' ? $collectionid : 0,
            'calendarjson' => $calendar, 'timemodified' => time(),
        ]);
    }
    /**
     * Live editorial swap contract. Deliberately reuse the published calendar JSON:
     * no dependence on mutable slots or anthology book IDs after publication.
     * A different book can replace a fictitious one at the SAME chapter position.
     */
    public static function assert_swap_calendar_compatible(string $calendarjson, array $snapshot): void {
        $cal = json_decode($calendarjson, true);
        if (!is_array($cal) || ($cal['schema'] ?? null) !== 1 ||
                ($cal['timezone'] ?? null) !== 'Europe/Paris' ||
                !is_array($cal['slots'] ?? null) || !array_is_list($cal['slots']) ||
                !is_int($cal['libraryafter'] ?? null)) {
            throw new \invalid_parameter_exception('Calendrier publié invalide : bascule interdite.');
        }
        $chapters = $snapshot['chapters'] ?? null;
        if (!is_array($chapters) || !array_is_list($chapters) ||
                count($chapters) !== count($cal['slots']) || count($chapters) < 1 ||
                count($chapters) > 31) {
            throw new \invalid_parameter_exception('La nouvelle version doit conserver exactement le nombre de cases publiées.');
        }
        // Validate the exact published mapping and release times, not live collection rows.
        $seen = [];
        $last = 0;
        foreach ($cal['slots'] as $index => $slot) {
            if (!is_array($slot) || !is_int($slot['position'] ?? null) ||
                    $slot['position'] !== $index + 1 ||
                    !is_int($slot['chapter'] ?? null) ||
                    $slot['chapter'] < 1 || $slot['chapter'] > count($chapters) ||
                    isset($seen[$slot['chapter']]) ||
                    !is_int($slot['unlockat'] ?? null) || $slot['unlockat'] <= $last) {
                throw new \invalid_parameter_exception('Les positions et les dates publiées doivent rester valides.');
            }
            $seen[$slot['chapter']] = true;
            $last = $slot['unlockat'];
        }
        if (count($seen) !== count($chapters) || $cal['libraryafter'] <= $last) {
            throw new \invalid_parameter_exception('Correspondance calendrier/recueil non cohérente.');
        }
        // The signed anthology snapshot contract already guarantees contiguous chapter positions.
        foreach ($chapters as $i => $chapter) {
            if (!is_array($chapter) || ($chapter['position'] ?? null) !== $i + 1) {
                throw new \invalid_parameter_exception('Positions de chapitres non conformes.');
            }
        }
    }

    /**
     * Atomically switch ONLY the revision pointer of an already published edition.
     * Existing slug, productsku, grants, frozen dates, publication time and status survive.
     * $expectedrevision protects administrators from stale forms/concurrent decisions.
     */
    public static function swap_published_revision(int $id, int $expectedrevision, int $newrevision): void {
        global $DB, $USER;
        if ($id < 1 || $expectedrevision < 1 || $newrevision < 1 || $newrevision === $expectedrevision) {
            throw new \invalid_parameter_exception('Choisir une nouvelle version différente de la version publiée.');
        }
        // Fast reject a stale form before opening a DB transaction. The check is
        // repeated under the row lock to close the concurrent-administrator race.
        $current = self::get($id);
        if ((string)$current->status !== 'published' ||
                (int)$current->revisionid !== $expectedrevision ||
                trim((string)($current->productsku ?? '')) === '' ||
                (int)$current->publishedat <= 0) {
            throw new \invalid_parameter_exception('Cette publication a changé : recharge la page avant de publier.');
        }
        $tx = $DB->start_delegated_transaction();
        try {
        // Moodle DEV uses mysqli. Lock across validation + the single-row pointer switch.
        $pub = $DB->get_record_sql(
            'SELECT * FROM {' . self::TABLE . '} WHERE id = :id FOR UPDATE', ['id' => $id]);
        if (!$pub || (string)$pub->status !== 'published' ||
                (int)$pub->revisionid !== $expectedrevision ||
                trim((string)($pub->productsku ?? '')) === '' ||
                (int)$pub->publishedat <= 0) {
            throw new \invalid_parameter_exception('Publication modifiée entre-temps, suspendue ou sans produit : aucune modification.');
        }
        // Revalidate the exact Native Commerce Service/entitlement binding.
        $prod = $DB->get_record('local_subs_commerce_product', ['sku' => (string)$pub->productsku],
            'id,sku,type,status', MUST_EXIST);
        if ((string)$prod->type !== 'service' || (string)$prod->status !== 'active') {
            throw new \invalid_parameter_exception('Le produit Commerce lié doit rester actif et de type Service.');
        }
        $ents = array_values($DB->get_records('local_subs_commerce_prod_ent', ['productid' => (int)$prod->id]));
        if (count($ents) !== 1 || (string)$ents[0]->type !== 'reader_access' ||
                (string)$ents[0]->resourcekey !== self::key((string)$pub->slug)) {
            throw new \invalid_parameter_exception('Le droit Reader lié au produit est divergent.');
        }
        // Approved signed snapshot + every private stored media checked BEFORE the switch.
        $snapshot = self::approved((int)$pub->anthologyid, $newrevision);
        if ((string)$pub->kind === 'calendar') {
            self::assert_swap_calendar_compatible((string)$pub->calendarjson, $snapshot);
            $cal = json_decode((string)$pub->calendarjson, true);
            if (($cal['releasepolicy'] ?? '') === 'chapter-ready-v1') {
                $old = self::approved((int)$pub->anthologyid, (int)$pub->revisionid);
                foreach ($old['chapters'] as $i => $oldchapter) {
                    if (($oldchapter['release_ready'] ?? false) === true &&
                            ($snapshot['chapters'][$i]['release_ready'] ?? false) !== true) {
                        throw new \invalid_parameter_exception('Impossible de refermer une histoire déjà validée lors du remplacement éditorial.');
                    }
                }
            }
        } else if ((string)$pub->kind !== 'anthology') {
            throw new \invalid_parameter_exception('Type de publication inconnu.');
        }
        $DB->update_record(self::TABLE, (object)[
            'id' => $id, 'revisionid' => $newrevision, 'timemodified' => time(),
        ]);
        $tx->allow_commit();
        } catch (\Throwable $e) {
            $tx->rollback($e); // Roll back the lock and any pending pointer write.
            throw $e; // Defensive: Moodle rollback normally rethrows.
        }
        // No customer text, credentials or URLs in this private server-side audit marker.
        error_log('[CampusFR D2.16.3 edition-swap] publication=' . $id .
            ' old_revision=' . $expectedrevision . ' new_revision=' . $newrevision .
            ' actor=' . (int)($USER->id ?? 0));
    }

    /**
     * Display copy only, editable in FR/EN/RU without touching frozen release dates.
     * Hash + row lock prevent an old CRM form from overwriting a newer editorial update.
     * Does not touch rights, status, revisions, SKU or publication timestamps.
     */
    public static function update_calendar_display(int $id, string $expectedhash, array $input): void {
        global $DB, $USER;
        if ($id < 1 || !preg_match('/^[a-f0-9]{64}$/D', $expectedhash)) {
            throw new \invalid_parameter_exception('Formulaire expiré : recharge la publication.');
        }
        $clean = [];
        foreach (\local_campusreader\local\calendar_presentation::LANGUAGES as $lang) {
            if (!isset($input[$lang]) || !is_array($input[$lang])) {
                throw new \invalid_parameter_exception('Traductions de présentation incomplètes.');
            }
            foreach (\local_campusreader\local\calendar_presentation::FIELDS as $field => $limit) {
                $value = $input[$lang][$field] ?? null;
                if (!is_string($value)) {
                    throw new \invalid_parameter_exception('Champ de présentation manquant.');
                }
                $value = trim($value);
                if (($field !== 'subtitle' && $value === '') || \core_text::strlen($value) > $limit ||
                        preg_match('/[\x00-\x1f\x7f<>]/u', $value)) {
                    throw new \invalid_parameter_exception('Texte de présentation invalide (' . $lang . '/' . $field . ').');
                }
                $clean[$lang][$field] = $value;
            }
        }
        $tx = $DB->start_delegated_transaction();
        try {
            $pub = $DB->get_record_sql('SELECT * FROM {' . self::TABLE . '} WHERE id = :id FOR UPDATE',
                ['id' => $id]);
            if (!$pub || (string)$pub->kind !== 'calendar' ||
                    !in_array((string)$pub->status, ['draft', 'published'], true) ||
                    !hash_equals($expectedhash, hash('sha256', (string)$pub->calendarjson))) {
                throw new \invalid_parameter_exception('Publication modifiée : recharge la page avant de sauvegarder.');
            }
            $calendar = json_decode((string)$pub->calendarjson, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($calendar) || ($calendar['schema'] ?? null) !== 1 ||
                    ($calendar['timezone'] ?? null) !== 'Europe/Paris' ||
                    !is_array($calendar['slots'] ?? null) || count($calendar['slots']) < 1) {
                throw new \invalid_parameter_exception('Calendrier figé invalide.');
            }
            $calendar['display'] = $clean;
            $DB->update_record(self::TABLE, (object)[
                'id' => $id,
                'calendarjson' => json_encode($calendar, JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'timemodified' => time(),
            ]);
            $tx->allow_commit();
        } catch (\Throwable $e) {
            $tx->rollback($e);
            throw $e;
        }
        // No editorial text or customer information in logs.
        error_log('[CampusFR D2.22.1 calendar-display] publication=' . $id .
            ' actor=' . (int)($USER->id ?? 0));
    }

    /** A publication-product binding is intentionally permanent to avoid orphan grants. */
    public static function bind_product(int $id, string $sku): void {
        global $DB;
        $pub = self::get($id);
        if ($pub->status !== 'draft') {
            throw new \invalid_parameter_exception('Publication non modifiable.');
        }
        $sku = strtoupper(trim($sku));
        if ($sku === '') { throw new \invalid_parameter_exception('Choisir un produit Commerce.'); }
        if ($pub->productsku) {
            if (strtoupper((string)$pub->productsku) === $sku) { return; }
            throw new \invalid_parameter_exception(
                'Le produit est déjà associé. Cette liaison est conservée pour protéger les droits existants.');
        }
        $prod = $DB->get_record('local_subs_commerce_product', ['sku' => $sku], '*', MUST_EXIST);
        if ((string)$prod->type !== 'service') {
            throw new \invalid_parameter_exception('Utiliser un produit Native de type Service.');
        }
        if (!self::eligible_product_status((string)$prod->status)) {
            throw new \invalid_parameter_exception('Le produit doit être Brouillon ou Inactif, jamais actif.');
        }
        if ($DB->record_exists_select(self::TABLE, 'productsku = :sku AND id <> :id',
                ['sku' => $sku, 'id' => $id])) {
            throw new \invalid_parameter_exception('Ce produit appartient déjà à une autre publication.');
        }
        if ($DB->record_exists('local_subs_commerce_prod_ent', ['productid' => (int)$prod->id])) {
            throw new \invalid_parameter_exception('Le produit possède déjà des droits : utiliser un Service QA neuf.');
        }
        self::assert_product_unused((int)$prod->id, $sku);
        $desired = self::key((string)$pub->slug);
        $tx = $DB->start_delegated_transaction();
        $manager = (new CommerceCatalogFactory($DB))->product_manager();
        $manager->save_entitlements($sku, [new CommerceProductEntitlementDefinition(
            $sku, 'reader_access', $desired, null, 1, [], 0
        )]);
        $DB->update_record(self::TABLE, (object)[
            'id' => $id, 'productsku' => $sku, 'timemodified' => time(),
        ]);
        $tx->allow_commit();
    }
    /** Publishing affects only the selected approved immutable revision and frozen dates. */
    public static function publish(int $id): void {
        global $DB;
        $pub = self::get($id);
        if ($pub->status !== 'draft' || !$pub->productsku) {
            throw new \invalid_parameter_exception('Version et produit Commerce obligatoires.');
        }
        $snapshot = self::approved((int)$pub->anthologyid, (int)$pub->revisionid);
        if ($pub->kind === 'calendar') {
            // Revalidate the frozen layout structurally, never silently update published slots.
            $cal = json_decode((string)$pub->calendarjson, true);
            if (!is_array($cal) || empty($cal['slots']) ||
                    count($cal['slots']) !== count($snapshot['chapters'])) {
                throw new \invalid_parameter_exception('Calendrier figé incomplet.');
            }
        }
        $prod = $DB->get_record('local_subs_commerce_product', ['sku' => (string)$pub->productsku],
            'id,sku,type,status', MUST_EXIST);
        if ($prod->type !== 'service' || $prod->status !== 'active') {
            throw new \invalid_parameter_exception('Le produit Native doit être actif.');
        }
        $ents = array_values($DB->get_records('local_subs_commerce_prod_ent', ['productid' => $prod->id]));
        if (count($ents) !== 1 || $ents[0]->type !== 'reader_access' ||
                $ents[0]->resourcekey !== self::key((string)$pub->slug)) {
            throw new \invalid_parameter_exception('Droit reader_access absent ou divergent.');
        }
        $DB->update_record(self::TABLE, (object)['id' => $id, 'status' => 'published',
            'publishedat' => time(), 'timemodified' => time()]);
    }
    /** Pausing access requires explicit CRM action. Resuming always requires revalidation. */
    public static function pause(int $id): void {
        global $DB;
        $pub = self::get($id);
        if ($pub->status !== 'published') { throw new \invalid_parameter_exception('Publication non active.'); }
        $DB->update_record(self::TABLE, (object)['id' => $id, 'status' => 'draft',
            'timemodified' => time()]);
    }
}
