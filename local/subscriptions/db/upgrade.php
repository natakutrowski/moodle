<?php

function xmldb_local_subscriptions_upgrade($oldversion) {
    global $DB;
	$dbman = $DB->get_manager();

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
		
		$table = new xmldb_table('subscription_plan');
		$field = new xmldb_field('highlight_type', XMLDB_TYPE_CHAR, '20', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
		upgrade_plugin_savepoint(true, 2025082602, 'local', 'subscriptions');
	}

    if ($oldversion < 2025082800) {
    	

        // A) subscription_plan.is_recurring
        $table = new xmldb_table('subscription_plan');
        $field = new xmldb_field('is_recurring', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'is_active');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // B) subscription_plan_price.stripe_price_id
        $table = new xmldb_table('subscription_plan_price');
        $field = new xmldb_field('stripe_price_id', XMLDB_TYPE_CHAR, '191', null, null, null, null, 'price'); // ou 'currency'
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // C) subscription_payment_request.last_update
        $table = new xmldb_table('subscription_payment_request');
        $field = new xmldb_field('subscription_payment_request', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025082800, 'local', 'subscriptions');
    }



    // … tes upgrades précédents …

    if ($oldversion < 2025082802) {
    	
        // Ajout des champs de jeton dans subscription_payment_request
        $table = new xmldb_table('subscription_payment_request');

        $field = new xmldb_field('login_token', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'reminder2_at');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('login_token_expires', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'login_token');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025082802, 'local', 'subscriptions');
    }

    if ($oldversion < 2025082904) {
    	
        $table = new xmldb_table('subscription_payment_request');

        // operation
        $field = new xmldb_field('operation', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'login_token_expires');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // reference_subscription_id
        $field = new xmldb_field('reference_subscription_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'operation');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // index operation
        $index = new xmldb_index('idx_spr_operation', XMLDB_INDEX_NOTUNIQUE, ['operation']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        // index ref sub id
        $index = new xmldb_index('idx_spr_refsubid', XMLDB_INDEX_NOTUNIQUE, ['reference_subscription_id']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2025082904, 'local', 'subscriptions');
    }

    if ($oldversion < 2025083000) {
    	

        // C) user_subscription.provider_*
        $table = new xmldb_table('user_subscription');
        $field = new xmldb_field('provider_name', XMLDB_TYPE_CHAR, '32', null, null, null, null, 'status');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

        $field = new xmldb_field('provider_subscription_id', XMLDB_TYPE_CHAR, '191', null, null, null, null, 'provider_name');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

        $field = new xmldb_field('provider_customer_id', XMLDB_TYPE_CHAR, '191', null, null, null, null, 'provider_subscription_id');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

        // Index
        $index = new xmldb_index('idx_us_provider_sub', XMLDB_INDEX_NOTUNIQUE, ['provider_subscription_id']);
        if (!$dbman->index_exists($table, $index)) { $dbman->add_index($table, $index); }

        upgrade_plugin_savepoint(true, 2025083000, 'local', 'subscriptions');
    }


	if ($oldversion < 2025083005) {
    	
		$table = new xmldb_table('user_subscription');
		$field = new xmldb_field('provider_name');

		// 1) Migration (si les deux colonnes coexistent)
		if ($DB->get_manager()->field_exists($table, $field)) {
			// Copie provider_name -> payment_provider lorsqu'il est vide
			// NB: moodle DML n'a pas de "update ... where ... is null" en batch simple pour concat,
			// on passe par une requête SQL directe.
			$sql = "UPDATE {user_subscription}
					SET payment_provider = provider_name
					WHERE (payment_provider IS NULL OR payment_provider = '')
					AND provider_name IS NOT NULL";
			$DB->execute($sql);
		}

		// 2) Suppression de la colonne
		if ($DB->get_manager()->field_exists($table, $field)) {
			$DB->get_manager()->drop_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2025083005, 'local', 'subscriptions');
	}

	if ($oldversion < 2025083009) {
    	
		$table = new xmldb_table('subscription_plan_price');

		// 1) Changer la précision à 10,2
		$field = new xmldb_field('price', XMLDB_TYPE_NUMBER, '10, 2', null, XMLDB_NOTNULL, null, '0.00', 'currency');
		if ($dbman->field_exists($table, $field)) {
			$dbman->change_field_precision($table, $field);
			$dbman->change_field_default($table, $field);
		}

		upgrade_plugin_savepoint(true, 2025083009, 'local', 'subscriptions');
	}

	if ($oldversion < 2025083012) {
    	
		$table = new xmldb_table('user_subscription');

		$field = new xmldb_field('last_invoice_id', XMLDB_TYPE_CHAR, '191', null, null, null, null, 'provider_customer_id');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		$index = new xmldb_index('idx_us_last_invoice', XMLDB_INDEX_NOTUNIQUE, ['last_invoice_id']);
		if (!$dbman->index_exists($table, $index)) { $dbman->add_index($table, $index); }

		upgrade_plugin_savepoint(true, 2025083012, 'local', 'subscriptions');
	}

	if ($oldversion < 2025083015) {
    	
		$table = new xmldb_table('user_subscription');

		$field = new xmldb_field('payment_failed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'provider_customer_id');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		$field = new xmldb_field('last_payment_failed_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'payment_failed');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		$field = new xmldb_field('last_payment_failed_reason', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'last_payment_failed_at');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		upgrade_plugin_savepoint(true, 2025083015, 'local', 'subscriptions');
	}

    if ($oldversion < 2025090500) {
    	
        // Étendre la longueur du champ operation à 64.
        $table = new xmldb_table('subscription_payment_request');

        // Index NON unique sur la colonne 'operation'.
        $index = new xmldb_index('operation', XMLDB_INDEX_NOTUNIQUE, ['operation']);

		$DB->execute("UPDATE {subscription_payment_request} SET operation = 'manual' WHERE operation IS NULL OR operation = ''");

        // 1) Drop l'index pour lever la dépendance.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }


        $field = new xmldb_field('operation', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);

        // change_field_precision = change la LENGTH d'un champ CHAR/VARCHAR.
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
            $dbman->change_field_default($table, $field);
			$dbman->change_field_notnull($table, $field); 
        }

        // 3) Recréer l'index.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Sauvegarde du point d'arrêt.
        upgrade_plugin_savepoint(true, 2025090500, 'local', 'subscriptions');
    }

	if ($oldversion < 2025090600) {
    	
		$table = new xmldb_table('subscription_reminder_log');
		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('subscriptionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('remind_key', XMLDB_TYPE_CHAR, '8', null, XMLDB_NOTNULL);
			$table->add_field('sent_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('uniq_sub_key', XMLDB_KEY_UNIQUE, ['subscriptionid','remind_key']);

			$dbman->create_table($table);
		}
		upgrade_plugin_savepoint(true, 2025090600, 'local', 'subscriptions');
	}

	if ($oldversion < 2025091300) {
    	
		$table = new xmldb_table('subscription_event');

		if (!$dbman->table_exists($table)) {
			// Champs
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('subscriptionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('eventtype', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
			$table->add_field('provider_event_id', XMLDB_TYPE_CHAR, '64', null, null, null);
			$table->add_field('occurred_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('payload_json', XMLDB_TYPE_TEXT, 'big', null, null, null);

			// Keys
			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

			// Create table
			$dbman->create_table($table);

			// Indexes (après création)
			$index = new xmldb_index('subscriptionid_idx', XMLDB_INDEX_NOTUNIQUE, ['subscriptionid']);
			$dbman->add_index($table, $index);

			$index = new xmldb_index('eventtype_idx', XMLDB_INDEX_NOTUNIQUE, ['eventtype']);
			$dbman->add_index($table, $index);

			$index = new xmldb_index('provider_event_id_idx', XMLDB_INDEX_NOTUNIQUE, ['provider_event_id']);
			$dbman->add_index($table, $index);
		}

		upgrade_plugin_savepoint(true, 2025091300, 'local', 'subscriptions');
	}

    // === 2025100100 : add descriptionformat on translations =================
    if ($oldversion < 2025100100) {
    	

        // Plan translations.
        $table = new xmldb_table('subscription_plan_translation');
        $field = new xmldb_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'description');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Access scope translations.
        $table2 = new xmldb_table('subscription_access_scope_translation');
        $field2 = new xmldb_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'description');
        if (!$dbman->field_exists($table2, $field2)) {
            $dbman->add_field($table2, $field2);
        }

        // Backfill (par sécurité, selon SGBD le DEFAULT ne remplit pas toujours l'existant).
        $DB->execute("UPDATE {subscription_plan_translation} SET descriptionformat = 1 WHERE descriptionformat IS NULL");
        $DB->execute("UPDATE {subscription_access_scope_translation} SET descriptionformat = 1 WHERE descriptionformat IS NULL");

        // Savepoint.
        upgrade_plugin_savepoint(true, 2025100100, 'local', 'subscriptions');
    }	

    // Bump ta version cible (adapte le numéro à ton plugin).
    if ($oldversion < 2025100601) {
    	
        $table = new xmldb_table('subscription_plan');

        // Drop uniquement le champ 'description' (tu gardes tout le reste).
        $field = new xmldb_field('description');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025100601, 'local', 'subscriptions');
    }



    if ($oldversion < 2025102000) {
        // Table des réponses envoyées via quickreply.php
        $table = new xmldb_table('local_subs_contact_reply');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id',          XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('adminid',     XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('messageid',   XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);   // id de block_edly_contact_msg (optionnel)
            $table->add_field('toemail',     XMLDB_TYPE_CHAR,    '255', null, null, null, null);
            $table->add_field('toname',      XMLDB_TYPE_CHAR,    '255', null, null, null, null);
            $table->add_field('lang',        XMLDB_TYPE_CHAR,     '20', null, null, null, null);
            $table->add_field('subject',     XMLDB_TYPE_CHAR,    '255', null, null, null, null);
            $table->add_field('bodyhtml',    XMLDB_TYPE_TEXT,    null,  null, null, null, null);
            $table->add_field('bodytext',    XMLDB_TYPE_TEXT,    null,  null, null, null, null);
            $table->add_field('ip',          XMLDB_TYPE_CHAR,     '64', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_time',   XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
            $table->add_index('idx_admin',  XMLDB_INDEX_NOTUNIQUE, ['adminid']);
            $table->add_index('idx_msgid',  XMLDB_INDEX_NOTUNIQUE, ['messageid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025102000, 'local', 'subscriptions');
    }

	if ($oldversion < 2025102700) {
		$table = new xmldb_table('subscription_payment_request');

		// created_ip
		$field = new xmldb_field('created_ip', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'price');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// created_useragent
		$field = new xmldb_field('created_useragent', XMLDB_TYPE_TEXT, null, null, null, null, null, 'created_ip');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// accept_language
		$field = new xmldb_field('accept_language', XMLDB_TYPE_CHAR, '191', null, null, null, null, 'created_useragent');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// http_referer
		$field = new xmldb_field('http_referer', XMLDB_TYPE_TEXT, null, null, null, null, null, 'accept_language');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2025102700, 'local', 'subscriptions');
	}

	if ($oldversion < 2025103001) {
		global $DB;
		$dbman = $DB->get_manager();

		// 1) Champ is_trial dans subscription_plan
		$table = new xmldb_table('subscription_plan');
		$field = new xmldb_field('is_trial', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'is_recurring');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// 2) Champs discount_* dans user_subscription
		$table = new xmldb_table('user_subscription');

		$field = new xmldb_field('discount_percent', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'status');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// discount_reason : nullable, PAS de default '' (laisser NULL)
		$field = new xmldb_field('discount_reason', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'discount_percent');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}


		// 3) Defaults de config pour l’essai
		set_config('trial_duration_days', 7,  'local_subscriptions');
		set_config('trial_discount_percent', 15, 'local_subscriptions');
		set_config('trial_discount_hours',   72, 'local_subscriptions');
		set_config('trial_plan_id', 0, 'local_subscriptions');

		upgrade_plugin_savepoint(true, 2025103001, 'local', 'subscriptions');
	}

	if ($oldversion < 2025103002) {
		$dbman = $DB->get_manager();

		// Champ discount_amount dans user_subscription
		$table = new xmldb_table('user_subscription');
		$field = new xmldb_field('discount_amount', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00', 'discount_percent');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2025103002, 'local', 'subscriptions');
	}

	if ($oldversion < 2025103004) {
		$dbman = $DB->get_manager();
		$table = new xmldb_table('subscription_payment_request');

		$field = new xmldb_field('locked_list_price', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00', null);
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		$field = new xmldb_field('locked_discount_percent', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'locked_list_price');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		$field = new xmldb_field('locked_discount_amount', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00', 'locked_discount_percent');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		// nullable, pas de default
		$field = new xmldb_field('locked_discount_reason', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'locked_discount_amount');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		$field = new xmldb_field('locked_final_price', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00', 'locked_discount_reason');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		$field = new xmldb_field('locked_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'locked_final_price');
		if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }

		upgrade_plugin_savepoint(true, 2025103004, 'local', 'subscriptions');
	}

	if ($oldversion < 2025110101) {
		$dbman = $DB->get_manager();
		$table = new xmldb_table('subscription_payment_request');

		$field = new xmldb_field('amount_minor', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'price');
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2025110101, 'local', 'subscriptions');
	}

	if ($oldversion < 2025110502) {
		$dbman = $DB->get_manager();
		$table = new xmldb_table('subscription_plan');

		$f1 = new xmldb_field('expiry_reminder_days', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'is_trial');
		if (!$dbman->field_exists($table, $f1)) {
			$dbman->add_field($table, $f1);
		}

		$f2 = new xmldb_field('expiry_reminder_enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'expiry_reminder_days');
		if (!$dbman->field_exists($table, $f2)) {
			$dbman->add_field($table, $f2);
		}

		upgrade_plugin_savepoint(true, 2025110502, 'local', 'subscriptions');
	}


    if ($oldversion < 2025112300) { // <-- use your own next plugin version here
        $table = new xmldb_table('subscription_payment_request');

        $field = new xmldb_field('phone', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'lastname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('phone_country', XMLDB_TYPE_CHAR, '10', null, null, null, null, 'phone');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025112300, 'local', 'subscriptions');
    }

	if ($oldversion < 2026051000) {
		$dbman = $DB->get_manager();

		// Table: subscription_digital_product.
		$table = new xmldb_table('subscription_digital_product');

		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('slug', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL);
			$table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
			$table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
			$table->add_field('filename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
			$table->add_field('price_eur', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00');
			$table->add_field('price_rub', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00');
			$table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
			$table->add_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
			$table->add_field('last_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

			$table->add_index('slug_unique', XMLDB_INDEX_UNIQUE, ['slug']);
			$table->add_index('enabled_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled']);

			$dbman->create_table($table);
		}

		// Table: subscription_digital_payment_request.
		$table = new xmldb_table('subscription_digital_payment_request');

		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('productid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
			$table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, null);
			$table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, null);
			$table->add_field('currency', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);
			$table->add_field('price', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00');
			$table->add_field('amount_minor', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
			$table->add_field('payment_provider', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
			$table->add_field('sessionid', XMLDB_TYPE_CHAR, '255', null, null);
			$table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
			$table->add_field('transactionid', XMLDB_TYPE_CHAR, '255', null, null);
			$table->add_field('payment_link', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('response_json', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('created_ip', XMLDB_TYPE_CHAR, '45', null, null);
			$table->add_field('created_useragent', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('accept_language', XMLDB_TYPE_CHAR, '191', null, null);
			$table->add_field('http_referer', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('download_token', XMLDB_TYPE_CHAR, '64', null, null);
			$table->add_field('download_token_expires', XMLDB_TYPE_INTEGER, '10', null, null);
			$table->add_field('emailsent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
			$table->add_field('receipt_sent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
			$table->add_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
			$table->add_field('last_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
			$table->add_field('payment_date', XMLDB_TYPE_INTEGER, '10', null, null);
			$table->add_field('expiration_date', XMLDB_TYPE_INTEGER, '10', null, null);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('productid_fk', XMLDB_KEY_FOREIGN, ['productid'], 'subscription_digital_product', ['id']);

			$table->add_index('idx_sdpr_email', XMLDB_INDEX_NOTUNIQUE, ['email']);
			$table->add_index('idx_sdpr_sessionid', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
			$table->add_index('idx_sdpr_status', XMLDB_INDEX_NOTUNIQUE, ['status']);
			$table->add_index('idx_sdpr_token', XMLDB_INDEX_UNIQUE, ['download_token']);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(true, 2026051000, 'local', 'subscriptions');
	}

	if ($oldversion < 2026051002) {
        $dbman = $DB->get_manager();

        $table = new xmldb_table('subscription_digital_payment_request');

        $fields = [
            new xmldb_field('attempts', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0'),
            new xmldb_field('last_attempt', XMLDB_TYPE_INTEGER, '10', null, null),
            new xmldb_field('last_error', XMLDB_TYPE_TEXT, null, null, null),
            new xmldb_field('locked_list_price', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00'),
            new xmldb_field('locked_discount_percent', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0'),
            new xmldb_field('locked_discount_amount', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00'),
            new xmldb_field('locked_discount_reason', XMLDB_TYPE_CHAR, '255', null, null),
            new xmldb_field('locked_final_price', XMLDB_TYPE_NUMBER, '12, 2', null, XMLDB_NOTNULL, null, '0.00'),
            new xmldb_field('locked_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2026051002, 'local', 'subscriptions');
    }

	if ($oldversion < 2026051003) {

		$dbman = $DB->get_manager();

		$table = new xmldb_table('subscription_digital_product');

		$fields = [
			new xmldb_field('coverimage', XMLDB_TYPE_CHAR, '255', null, null),
			new xmldb_field('sales_intro', XMLDB_TYPE_TEXT, null, null, null),
			new xmldb_field('content_items', XMLDB_TYPE_TEXT, null, null, null),
			new xmldb_field('forwho_items', XMLDB_TYPE_TEXT, null, null, null),
			new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0'),
		];

		foreach ($fields as $field) {
			if (!$dbman->field_exists($table, $field)) {
				$dbman->add_field($table, $field);
			}
		}

		upgrade_plugin_savepoint(true, 2026051003, 'local', 'subscriptions');
	}

	if ($oldversion < 2026051004) {

		$dbman = $DB->get_manager();

		$table = new xmldb_table('subscription_digital_product_lang');

		if (!$dbman->table_exists($table)) {

			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);

			$table->add_field('productid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

			$table->add_field('lang', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL);

			$table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);

			$table->add_field('sales_intro', XMLDB_TYPE_TEXT);

			$table->add_field('content_items', XMLDB_TYPE_TEXT);

			$table->add_field('forwho_items', XMLDB_TYPE_TEXT);

			$table->add_field('creation_date', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

			$table->add_field('last_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

			$table->add_key(
				'productid_fk',
				XMLDB_KEY_FOREIGN,
				['productid'],
				'subscription_digital_product',
				['id']
			);

			$table->add_index(
				'product_lang_uix',
				XMLDB_INDEX_UNIQUE,
				['productid', 'lang']
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(true, 2026051004, 'local', 'subscriptions');
	}

	if ($oldversion < 2026051005) {
		$dbman = $DB->get_manager();

		$table = new xmldb_table('subscription_digital_payment_request');
		$field = new xmldb_field('buyer_lang', XMLDB_TYPE_CHAR, '10', null, null);

		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2026051005, 'local', 'subscriptions');
	}

	if ($oldversion < 2026051006) {

		$dbman = $DB->get_manager();

		$table = new xmldb_table('subscription_digital_product');

		$field = new xmldb_field('mobile_filename', XMLDB_TYPE_CHAR, '255', null, null);

		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2026051006, 'local', 'subscriptions');
	}	

    return true;
}
