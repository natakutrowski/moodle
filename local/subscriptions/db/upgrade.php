<?php

function xmldb_local_subscriptions_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2025061101) {
        $table = new xmldb_table('user_subscription');
        $field = new xmldb_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025061101, 'local', 'subscriptions');
    }
    
    if ($oldversion < 2025061302) {

		// Table: subscription_access_scope
		$table = new xmldb_table('subscription_access_scope');
		if (!$DB->get_manager()->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
			$table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('course_ids', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
			$table->add_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('last_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
	
			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('name_unique', XMLDB_KEY_UNIQUE, ['name']);
	
			$DB->get_manager()->create_table($table);
		}
	
		// Table: subscription_plan
		$table = new xmldb_table('subscription_plan');
		if (!$DB->get_manager()->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
			$table->add_field('access_scope_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('duration_days', XMLDB_TYPE_INTEGER, '10');
			$table->add_field('description', XMLDB_TYPE_TEXT, null);
			$table->add_field('is_active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1);
			$table->add_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('last_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
	
			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('access_scope_fk', XMLDB_KEY_FOREIGN, ['access_scope_id'], 'subscription_access_scope', ['id']);
	
			$DB->get_manager()->create_table($table);
		}
	
		// Table: subscription_plan_price
		$table = new xmldb_table('subscription_plan_price');
		if (!$DB->get_manager()->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('plan_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('currency', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);
			$table->add_field('price', XMLDB_TYPE_NUMBER, '10', null, XMLDB_NOTNULL, null, '2');
	
			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('plan_fk', XMLDB_KEY_FOREIGN, ['plan_id'], 'subscription_plan', ['id']);
			$table->add_key('plan_currency_unique', XMLDB_KEY_UNIQUE, ['plan_id', 'currency']);
	
			$DB->get_manager()->create_table($table);
		}
	
		// ✅ Upgrade OK
		upgrade_plugin_savepoint(true, 2025061302, 'local', 'subscriptions');
	}
	
	if ($oldversion < 2025061303) {
		$table = new xmldb_table('subscription_access_scope_translation');
	
		if (!$DB->get_manager()->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
			$table->add_field('scope_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
			$table->add_field('lang', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
			$table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
			$table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
	
			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('scopefk', XMLDB_KEY_FOREIGN, ['scope_id'], 'subscription_access_scope', ['id']);
			$table->add_index('lang_scope_unique', XMLDB_INDEX_UNIQUE, ['scope_id', 'lang']);
	
			$DB->get_manager()->create_table($table);
		}
	
		upgrade_plugin_savepoint(true, 2025061303, 'local', 'subscriptions');
	}
	
	if ($oldversion < 2025061304) {

		// Ajouter les champs creation_date et last_update à la table des traductions.
		$table = new xmldb_table('subscription_access_scope_translation');
	
		$field1 = new xmldb_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		if (!$DB->get_manager()->field_exists($table, $field1)) {
			$DB->get_manager()->add_field($table, $field1);
		}
	
		$field2 = new xmldb_field('last_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		if (!$DB->get_manager()->field_exists($table, $field2)) {
			$DB->get_manager()->add_field($table, $field2);
		}
	
		// Upgrade savepoint.
		upgrade_plugin_savepoint(true, 2025061304, 'local', 'subscriptions');
	}

	if ($oldversion < 2025061401) {
	
		// Supprimer le champ description devenu inutile (stocké maintenant dans les traductions).
		$table = new xmldb_table('subscription_access_scope');
		$field = new xmldb_field('description');
	
		if (!$DB->get_manager()->field_exists($table, $field)) {
			!$DB->get_manager()->drop_field($table, $field);
		}
	
		upgrade_plugin_savepoint(true, 2025061401, 'local', 'subscriptions');
	}
	
	if ($oldversion < 2025061502) {

		// Suppression du champ "description" de la table "subscription_plan".
		$table = new xmldb_table('subscription_plan');
		$field = new xmldb_field('description');
		if (!$DB->get_manager()->field_exists($table, $field)) {
			!$DB->get_manager()->drop_field($table, $field);
		}
	
		// Création de la table "subscription_plan_translation".
		$table = new xmldb_table('subscription_plan_translation');
	
		if (!$DB->get_manager()->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
			$table->add_field('plan_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
			$table->add_field('lang', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
			$table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
			$table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
			$table->add_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
			$table->add_field('last_update', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
	
			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('plan_id_fk', XMLDB_KEY_FOREIGN, ['plan_id'], 'subscription_plan', ['id']);
			$table->add_index('lang_plan_unique', XMLDB_INDEX_UNIQUE, ['plan_id', 'lang']);
	
			!$DB->get_manager()->create_table($table);
		}
	
		// Enregistre la mise à jour
		upgrade_plugin_savepoint(true, 2025061502, 'local', 'subscriptions');
	}

    if ($oldversion < 2025061903) {

        // 1. Ajouter la nouvelle colonne duration_key.
        $table = new xmldb_table('subscription_plan');
        $field = new xmldb_field('duration_key', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, '1month', 'access_scope_id');
        if (!$DB->get_manager()->field_exists($table, $field)) {
            $DB->get_manager()->add_field($table, $field);
        }

        // 2. Migrer les anciennes valeurs de duration_days vers des clés.
        $durations = [
            30 => '1month',
            90 => '3months',
            180 => '6months',
            365 => '1year',
            1095 => '3years',
            NULL => 'lifetime'
        ];

        foreach ($durations as $days => $key) {
            $DB->execute("UPDATE {subscription_plan} SET duration_key = ? WHERE duration_days = ?", [$key, $days]);
        }

        // 3. Supprimer la colonne duration_days.
        if ($DB->get_manager()->field_exists($table, 'duration_days')) {
            $DB->get_manager()->drop_field($table, new xmldb_field('duration_days'));
        }

        // 4. Bump version.
        upgrade_plugin_savepoint(true, 2025061903, 'local', 'subscriptions');
    }

	if ($oldversion < 2025061904) {
		// Ajout d'un index unique sur le champ name (insensible à la casse si utf8_bin).
		$table = new xmldb_table('subscription_plan');
		$index = new xmldb_index('uniq_name', XMLDB_INDEX_UNIQUE, ['name']);
		if (!$DB->get_manager()->index_exists($table, $index)) {
			$DB->get_manager()->add_index($table, $index);
		}

		upgrade_plugin_savepoint(true, 2025061904, 'local', 'subscriptions');
	}


    return true;
}
