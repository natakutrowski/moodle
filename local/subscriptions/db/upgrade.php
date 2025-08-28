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

	if ($oldversion < 2025062100) {

		$dbman = $DB->get_manager();
		$table = new xmldb_table('user_subscription');

		$fields = [
			new xmldb_field('planid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0),
			new xmldb_field('pricepaid', XMLDB_TYPE_NUMBER, '10,2'),
			new xmldb_field('currency', XMLDB_TYPE_CHAR, '10'),
			new xmldb_field('transaction_id', XMLDB_TYPE_CHAR, '255')
		];

		foreach ($fields as $field) {
			if (!$dbman->field_exists($table, $field)) {
				$dbman->add_field($table, $field);
			}
		}

		$oldfields = ['plan', 'subscription_id', 'access_scope'];
		foreach ($oldfields as $oldfieldname) {
			$oldfield = new xmldb_field($oldfieldname);
			if ($dbman->field_exists($table, $oldfield)) {
				$dbman->drop_field($table, $oldfield);
			}
		}

		upgrade_plugin_savepoint(true, 2025062100, 'local', 'subscriptions');
	}


    // === Création de la table subscription_payment_request ===
    if ($oldversion < 2025062901) {

		$dbman = $DB->get_manager();
        $table = new xmldb_table('subscription_payment_request');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('planid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('currency', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('price', XMLDB_TYPE_NUMBER, '10,2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payment_provider', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('transactionid', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('payment_link', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('response_json', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('payment_date', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Crée la table si elle n’existe pas
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025062901, 'local', 'subscriptions');
    }

    // === Renommage des colonnes xxx_id -> xxxid pour cohérence ===
    if ($oldversion < 2025062902) {
		$dbman = $DB->get_manager();

        // 1. user_subscription: transaction_id -> transactionid
        $table = new xmldb_table('user_subscription');
        $field = new xmldb_field('transaction_id', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'currency');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'transactionid');
        }

        // 2. subscription_access_scope_translation: scope_id -> accessscopeid
        $table = new xmldb_table('subscription_access_scope_translation');
        $field = new xmldb_field('scope_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'accessscopeid');
        }

        // 3. subscription_plan: access_scope_id -> accessscopeid
        $table = new xmldb_table('subscription_plan');
        $field = new xmldb_field('access_scope_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'name');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'accessscopeid');
        }

        // 4. subscription_plan_translation: plan_id -> planid
        $table = new xmldb_table('subscription_plan_translation');
        $field = new xmldb_field('plan_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'planid');
        }

        // 5. subscription_plan_price: plan_id -> planid
        $table = new xmldb_table('subscription_plan_price');
        $field = new xmldb_field('plan_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'planid');
        }

        upgrade_plugin_savepoint(true, 2025062902, 'local', 'subscriptions');
    }

	if ($oldversion < 2025063002) {
		$dbman = $DB->get_manager();

		// Supprimer les anciennes colonnes.
		$table = new xmldb_table('subscription_payment_request');

		// Supprimer 'userid' s'il existe.
		$field = new xmldb_field('userid');
		if ($dbman->field_exists($table, $field)) {
			$dbman->drop_field($table, $field);
		}

		// Supprimer 'planid' s'il existe.
		$field = new xmldb_field('planid');
		if ($dbman->field_exists($table, $field)) {
			$dbman->drop_field($table, $field);
		}

		// Ajouter 'subscriptionid'.
		$field = new xmldb_field('subscriptionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Ajouter 'expiration_date'.
		$field = new xmldb_field('expiration_date', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// Ajouter un index sur subscriptionid.
		$index = new xmldb_index('subscriptionid_idx', XMLDB_INDEX_NOTUNIQUE, ['subscriptionid']);
		if (!$dbman->index_exists($table, $index)) {
			$dbman->add_index($table, $index);
		}


		upgrade_plugin_savepoint(true, 2025063002, 'local', 'subscriptions');
	}
    if ($oldversion < 2025082301) {
		$dbman = $DB->get_manager();
        $table = new xmldb_table('subscription_payment_request');

        // 1) subscriptionid -> nullable
        $field = new xmldb_field('subscriptionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

		$index = new xmldb_index('sub_ix', XMLDB_INDEX_NOTUNIQUE, ['subscriptionid']);
		if ($dbman->index_exists($table, $index)) {
			$dbman->drop_index($table, $index);
		}
        if ($dbman->field_exists($table, $field)) {
            // Certaines installations ont NOT NULL: on le rend NULLABLE.
            $dbman->change_field_notnull($table, $field);
        }

        // 2) Colonnes manquantes
        $fields = [
            // après id
            ['planid',        XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id'],
            ['userid',        XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'planid'],
            ['email',         XMLDB_TYPE_CHAR,    '255', null, null, null, null, 'userid'],
            ['firstname',     XMLDB_TYPE_CHAR,    '100', null, null, null, null, 'email'],
            ['lastname',      XMLDB_TYPE_CHAR,    '100', null, null, null, null, 'firstname'],

            // après payment_provider
            ['sessionid',     XMLDB_TYPE_CHAR,    '255', null, null, null, null, 'payment_provider'],
        ];
        foreach ($fields as [$name,$type,$len,$unsigned,$notnull,$seq,$default,$after]) {
            $f = new xmldb_field($name, $type, $len, $unsigned, $notnull, $seq, $default, $after);
            if (!$dbman->field_exists($table, $f)) {
                $dbman->add_field($table, $f);
            }
        }

        // 3) Normaliser creation_date/payment_date (défaut & nullability)
        $creation = new xmldb_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $creation)) {
            $dbman->change_field_default($table, $creation);
            $dbman->change_field_notnull($table, $creation);
        }
        $paydate = new xmldb_field('payment_date', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if ($dbman->field_exists($table, $paydate)) {
        }

        // 4) Index (créés si absents)
        $indexes = [
            new xmldb_index('idx_spr_sessionid', XMLDB_INDEX_NOTUNIQUE, ['sessionid']),
            new xmldb_index('idx_spr_planid',    XMLDB_INDEX_NOTUNIQUE, ['planid']),
            new xmldb_index('idx_spr_userid',    XMLDB_INDEX_NOTUNIQUE, ['userid']),
            new xmldb_index('idx_spr_status',    XMLDB_INDEX_NOTUNIQUE, ['status']),
        ];
        foreach ($indexes as $idx) {
            if (!$dbman->index_exists($table, $idx)) {
                $dbman->add_index($table, $idx);
            }
        }

        upgrade_plugin_savepoint(true, 2025082301, 'local', 'subscriptions');
    }

	if ($oldversion < 2025082401) {
		$dbman = $DB->get_manager();
		$table = new xmldb_table('subscription_payment_request');
		$field = new xmldb_field('emailsent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');

		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// (Optionnel) index pour filtres fréquents — ici pas nécessaire.
		// $index = new xmldb_index('idx_spr_emailsent', XMLDB_INDEX_NOTUNIQUE, ['emailsent']);
		// if (!$dbman->index_exists($table, $index)) {
		//     $dbman->add_index($table, $index);
		// }

		upgrade_plugin_savepoint(true, 2025082401, 'local', 'subscriptions');
	}


	if ($oldversion < 2025082501) {
		$dbman = $DB->get_manager();
		$table = new xmldb_table('subscription_payment_request');

		foreach ([
			new xmldb_field('attempts', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0'),
			new xmldb_field('last_attempt', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
			new xmldb_field('last_error', XMLDB_TYPE_TEXT, 'big', null, null, null, null),
			new xmldb_field('retry_token', XMLDB_TYPE_CHAR, '64', null, null, null, null),
			new xmldb_field('retry_expires', XMLDB_TYPE_INTEGER, '10', null, null, null, null),
		] as $f) {
			if (!$dbman->field_exists($table, $f)) { $dbman->add_field($table, $f); }
		}

		upgrade_plugin_savepoint(true, 2025082501, 'local', 'subscriptions');
	}

	if ($oldversion < 2025082601) {
		$dbman = $DB->get_manager();
		$table = new xmldb_table('subscription_payment_request');

		$fields = [
			new xmldb_field('reminder_stage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0'), // 0=aucune, 1=R1 envoyée, 2=R2 envoyée
			new xmldb_field('reminder1_at',   XMLDB_TYPE_INTEGER, '10', null, null, null, null),
			new xmldb_field('reminder2_at',   XMLDB_TYPE_INTEGER, '10', null, null, null, null),
		];
		foreach ($fields as $f) {
			if (!$dbman->field_exists($table, $f)) {
				$dbman->add_field($table, $f);
			}
		}

		upgrade_plugin_savepoint(true, 2025082601, 'local', 'subscriptions');
	}

	if ($oldversion < 2025082602) {
		$dbman = $DB->get_manager();
		$table = new xmldb_table('subscription_plan');
		$field = new xmldb_field('highlight_type', XMLDB_TYPE_CHAR, '20', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		upgrade_plugin_savepoint(true, 2025082602, 'local', 'subscriptions');
	}


    return true;
}
