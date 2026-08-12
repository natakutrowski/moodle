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

		// C) subscription_payment_request.last_update.
		$table = new xmldb_table(
			'subscription_payment_request'
		);

		$field = new xmldb_field(
			'last_update',
			XMLDB_TYPE_INTEGER,
			'10',
			null,
			XMLDB_NOTNULL,
			null,
			'0',
			'creation_date'
		);

		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field(
				$table,
				$field
			);
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

	if ($oldversion < 2026051008) {

		$dbman = $DB->get_manager();

		$table = new xmldb_table('subscription_digital_product_lang');

		$fields = [
			new xmldb_field('access_note', XMLDB_TYPE_TEXT, null, null, null),
			new xmldb_field('content_title', XMLDB_TYPE_TEXT, null, null, null),
			new xmldb_field('forwho_title', XMLDB_TYPE_TEXT, null, null, null),
			new xmldb_field('buy_title', XMLDB_TYPE_TEXT, null, null, null),
		];

		foreach ($fields as $field) {
			if (!$dbman->field_exists($table, $field)) {
				$dbman->add_field($table, $field);
			}
		}

		upgrade_plugin_savepoint(true, 2026051008, 'local', 'subscriptions');
	}
	
	if ($oldversion < 2026051009) {

		$dbman = $DB->get_manager();

		$table = new xmldb_table('subscription_digital_payment_request');

		$field = new xmldb_field(
			'userid',
			XMLDB_TYPE_INTEGER,
			'10',
			null,
			null,
			null,
			null
		);

		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2026051009, 'local', 'subscriptions');
	}

	if ($oldversion < 2026051010) {

		$dbman = $DB->get_manager();

		// Table: subscription_plan_entitlement.
		$table = new xmldb_table('subscription_plan_entitlement');

		if (!$dbman->table_exists($table)) {

			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('planid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('accesslevel', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'full');
			$table->add_field('roleshortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'student');
			$table->add_field('groupname', XMLDB_TYPE_CHAR, '255', null, null);
			$table->add_field('priority', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 100);
			$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('lastupdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('planid_fk', XMLDB_KEY_FOREIGN, ['planid'], 'subscription_plan', ['id']);
			$table->add_key('courseid_fk', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);

			$table->add_index('plan_course_level_uix', XMLDB_INDEX_UNIQUE, ['planid', 'courseid', 'accesslevel']);

			$dbman->create_table($table);
		}

		// Table: subscription_plan_upgrade.
		$table = new xmldb_table('subscription_plan_upgrade');

		if (!$dbman->table_exists($table)) {

			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('fromplanid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('toplanid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('pricingmode', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, 'difference');
			$table->add_field('isactive', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1);
			$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('lastupdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('fromplanid_fk', XMLDB_KEY_FOREIGN, ['fromplanid'], 'subscription_plan', ['id']);
			$table->add_key('toplanid_fk', XMLDB_KEY_FOREIGN, ['toplanid'], 'subscription_plan', ['id']);

			$table->add_index('upgrade_pair_uix', XMLDB_INDEX_UNIQUE, ['fromplanid', 'toplanid']);

			$dbman->create_table($table);
		}

		// Migration douce : créer des entitlements "full" depuis les anciens scopes.
		$sql = "SELECT p.id AS planid, s.course_ids
				FROM {subscription_plan} p
				JOIN {subscription_access_scope} s ON s.id = p.accessscopeid";

		$plans = $DB->get_records_sql($sql);

		foreach ($plans as $plan) {
			if (empty($plan->course_ids)) {
				continue;
			}

			$courseids = preg_split('/[,;\s]+/', (string)$plan->course_ids, -1, PREG_SPLIT_NO_EMPTY);
			$courseids = array_unique(array_map('intval', $courseids));

			foreach ($courseids as $courseid) {
				if ($courseid <= 0) {
					continue;
				}

				if ($DB->record_exists('subscription_plan_entitlement', [
					'planid' => (int)$plan->planid,
					'courseid' => $courseid,
					'accesslevel' => 'full',
				])) {
					continue;
				}

				$now = time();

				$DB->insert_record('subscription_plan_entitlement', (object)[
					'planid' => (int)$plan->planid,
					'courseid' => $courseid,
					'accesslevel' => 'full',
					'roleshortname' => 'student',
					'groupname' => '',
					'priority' => 100,
					'timecreated' => $now,
					'lastupdate' => $now,
				]);
			}
		}

		upgrade_plugin_savepoint(true, 2026051010, 'local', 'subscriptions');
	}

	if ($oldversion < 2026062903) {
		$table = new xmldb_table('local_subscriptions_admin_log');

		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('actorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('targetuserid', XMLDB_TYPE_INTEGER, '10', null, null);
			$table->add_field('action', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
			$table->add_field('objecttype', XMLDB_TYPE_CHAR, '50', null, null);
			$table->add_field('objectid', XMLDB_TYPE_INTEGER, '10', null, null);
			$table->add_field('details', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('ipaddress', XMLDB_TYPE_CHAR, '45', null, null);
			$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_index('actor_idx', XMLDB_INDEX_NOTUNIQUE, ['actorid']);
			$table->add_index('targetuser_idx', XMLDB_INDEX_NOTUNIQUE, ['targetuserid']);
			$table->add_index('action_idx', XMLDB_INDEX_NOTUNIQUE, ['action']);
			$table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(true, 2026062903, 'local', 'subscriptions');
	}

	if ($oldversion < 2026062904) {
		$table = new xmldb_table('local_subscriptions_user_note');

		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('authorid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('note', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
			$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
			$table->add_index('authorid_idx', XMLDB_INDEX_NOTUNIQUE, ['authorid']);
			$table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(true, 2026062904, 'local', 'subscriptions');
	}

	if ($oldversion < 2026062905) {
		$table = new xmldb_table('local_subscriptions_user_note');
		$field = new xmldb_field(
			'type',
			XMLDB_TYPE_CHAR,
			'30',
			null,
			XMLDB_NOTNULL,
			null,
			'general',
			'note'
		);

		if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		upgrade_plugin_savepoint(true, 2026062905, 'local', 'subscriptions');
	}

	if ($oldversion < 2026062906) {
		$table = new xmldb_table('local_subscriptions_user_tag');

		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
		$table->add_field('tag', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL);
		$table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

		$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

		$table->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
		$table->add_index('userid_tag_uix', XMLDB_INDEX_UNIQUE, ['userid', 'tag']);

		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(true, 2026062906, 'local', 'subscriptions');
	}

	if ($oldversion < 2026062908) {
		$dbman = $DB->get_manager();

		$table = new xmldb_table('local_subscriptions_automation_rule');

		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('rulekey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
			$table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
			$table->add_field('triggerkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
			$table->add_field('triggerpayload', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('conditionsjson', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('actionsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
			$table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1);
			$table->add_field('priority', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 100);
			$table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

			$table->add_index('rulekey_uix', XMLDB_INDEX_UNIQUE, ['rulekey']);
			$table->add_index('trigger_idx', XMLDB_INDEX_NOTUNIQUE, ['triggerkey']);
			$table->add_index('enabled_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
			$table->add_index('priority_idx', XMLDB_INDEX_NOTUNIQUE, ['priority']);

			$dbman->create_table($table);
		}

		$table = new xmldb_table('local_subscriptions_automation_history');

		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('rulekey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
			$table->add_field('triggerkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
			$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null);
			$table->add_field('entitytype', XMLDB_TYPE_CHAR, '50', null, null);
			$table->add_field('entityid', XMLDB_TYPE_INTEGER, '10', null, null);
			$table->add_field('status', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'success');
			$table->add_field('message', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('contextjson', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('resultjson', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
			$table->add_key('ruleid_fk', XMLDB_KEY_FOREIGN, ['ruleid'], 'local_subscriptions_automation_rule', ['id']);

			$table->add_index('trigger_idx', XMLDB_INDEX_NOTUNIQUE, ['triggerkey']);
			$table->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
			$table->add_index('entity_idx', XMLDB_INDEX_NOTUNIQUE, ['entitytype', 'entityid']);
			$table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(true, 2026062908, 'local', 'subscriptions');
	}

	if ($oldversion < 2026062910) {
		$table = new xmldb_table('local_subscriptions_crm_score');

		if (!$dbman->table_exists($table)) {
			$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
			$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
			$table->add_field('commercialscore', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('engagementscore', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('riskscore', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('globalscore', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
			$table->add_field('level', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL);
			$table->add_field('segmentsjson', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('opportunitiesjson', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('recommendationsjson', XMLDB_TYPE_TEXT, null, null, null);
			$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);

			$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

			$table->add_index('userid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid']);
			$table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
			$table->add_index('userid_time_idx', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);
			$table->add_index('global_idx', XMLDB_INDEX_NOTUNIQUE, ['globalscore']);
			$table->add_index('risk_idx', XMLDB_INDEX_NOTUNIQUE, ['riskscore']);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(true, 2026062910, 'local', 'subscriptions');
	}

	if ($oldversion < 2026071201) {
		$table = new xmldb_table(
			'local_subscriptions_inbox_account'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'name',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'email',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'provider',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'imap'
			);
			$table->add_field(
				'enabled',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'credentialkey',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'configurationjson',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'syncstatejson',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'lastsyncedat',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'lasterrorat',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'lasterror',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_index(
				'email_uix',
				XMLDB_INDEX_UNIQUE,
				['email']
			);
			$table->add_index(
				'provider_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['provider']
			);
			$table->add_index(
				'enabled_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['enabled']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_contact'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'displayname',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'primaryemail',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'normalizedemail',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'matcheduserid',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'matchstatus',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'unmatched'
			);
			$table->add_field(
				'matchsource',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'none'
			);
			$table->add_field(
				'matchconfidence',
				XMLDB_TYPE_NUMBER,
				'5',
				'2',
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'matchlocked',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'lastmatchedat',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'matcheduserid_fk',
				XMLDB_KEY_FOREIGN,
				['matcheduserid'],
				'user',
				['id']
			);

			$table->add_index(
				'normalizedemail_uix',
				XMLDB_INDEX_UNIQUE,
				['normalizedemail']
			);
			$table->add_index(
				'matchstatus_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['matchstatus']
			);
			$table->add_index(
				'reconcile_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['matchstatus', 'matchlocked']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_team'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'name',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'description',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'enabled',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				1
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_index(
				'name_uix',
				XMLDB_INDEX_UNIQUE,
				['name']
			);
			$table->add_index(
				'enabled_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['enabled']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_team_member'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'teamid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'userid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'teamid_fk',
				XMLDB_KEY_FOREIGN,
				['teamid'],
				'local_subscriptions_inbox_team',
				['id']
			);
			$table->add_key(
				'userid_fk',
				XMLDB_KEY_FOREIGN,
				['userid'],
				'user',
				['id']
			);

			$table->add_index(
				'team_user_uix',
				XMLDB_INDEX_UNIQUE,
				['teamid', 'userid']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_thread'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'accountid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'contactid',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'providerthreadid',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'subject',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'open'
			);
			$table->add_field(
				'priority',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				'normal'
			);
			$table->add_field(
				'assigneduserid',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'assignedteamid',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'folder',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'unreadcount',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'messagecount',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'lastmessageat',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'locallydeleted',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'accountid_fk',
				XMLDB_KEY_FOREIGN,
				['accountid'],
				'local_subscriptions_inbox_account',
				['id']
			);
			$table->add_key(
				'contactid_fk',
				XMLDB_KEY_FOREIGN,
				['contactid'],
				'local_subscriptions_inbox_contact',
				['id']
			);
			$table->add_key(
				'assigneduserid_fk',
				XMLDB_KEY_FOREIGN,
				['assigneduserid'],
				'user',
				['id']
			);
			$table->add_key(
				'assignedteamid_fk',
				XMLDB_KEY_FOREIGN,
				['assignedteamid'],
				'local_subscriptions_inbox_team',
				['id']
			);

			$table->add_index(
				'account_providerthread_uix',
				XMLDB_INDEX_UNIQUE,
				['accountid', 'providerthreadid']
			);
			$table->add_index(
				'status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['status']
			);
			$table->add_index(
				'priority_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['priority']
			);
			$table->add_index(
				'lastmessage_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['lastmessageat']
			);
			$table->add_index(
				'workqueue_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['locallydeleted', 'status', 'lastmessageat']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_message'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'threadid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'accountid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'providermessageid',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'providerparentid',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'folder',
				XMLDB_TYPE_CHAR,
				'191'
			);
			$table->add_field(
				'uidvalidity',
				XMLDB_TYPE_CHAR,
				'64'
			);
			$table->add_field(
				'provideruid',
				XMLDB_TYPE_CHAR,
				'64'
			);
			$table->add_field(
				'direction',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				'received'
			);
			$table->add_field(
				'subject',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'bodytext',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'bodyhtml',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'headersjson',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'inreplyto',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'referencesjson',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'receivedat',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'sentat',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'isread',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'hasattachments',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'checksum',
				XMLDB_TYPE_CHAR,
				'64'
			);
			$table->add_field(
				'createdby',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'threadid_fk',
				XMLDB_KEY_FOREIGN,
				['threadid'],
				'local_subscriptions_inbox_thread',
				['id']
			);
			$table->add_key(
				'accountid_fk',
				XMLDB_KEY_FOREIGN,
				['accountid'],
				'local_subscriptions_inbox_account',
				['id']
			);
			$table->add_key(
				'createdby_fk',
				XMLDB_KEY_FOREIGN,
				['createdby'],
				'user',
				['id']
			);

			$table->add_index(
				'provider_uid_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'accountid',
					'folder',
					'uidvalidity',
					'provideruid',
				]
			);
			$table->add_index(
				'provider_message_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['accountid', 'providermessageid']
			);
			$table->add_index(
				'direction_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['direction']
			);
			$table->add_index(
				'status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['status']
			);
			$table->add_index(
				'receivedat_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['receivedat']
			);
			$table->add_index(
				'sentat_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['sentat']
			);
			$table->add_index(
				'checksum_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['checksum']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_participant'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'messageid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'contactid',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'participanttype',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'email',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'normalizedemail',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'displayname',
				XMLDB_TYPE_CHAR,
				'255'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'messageid_fk',
				XMLDB_KEY_FOREIGN,
				['messageid'],
				'local_subscriptions_inbox_message',
				['id']
			);
			$table->add_key(
				'contactid_fk',
				XMLDB_KEY_FOREIGN,
				['contactid'],
				'local_subscriptions_inbox_contact',
				['id']
			);

			$table->add_index(
				'message_type_email_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'messageid',
					'participanttype',
					'normalizedemail',
				]
			);
			$table->add_index(
				'normalizedemail_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['normalizedemail']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_attachment'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'messageid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'providerattachmentid',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'filename',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'mimetype',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'filesize',
				XMLDB_TYPE_INTEGER,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'contenthash',
				XMLDB_TYPE_CHAR,
				'64'
			);
			$table->add_field(
				'fileitemid',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'downloadstatus',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				'pending'
			);
			$table->add_field(
				'lasterror',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'messageid_fk',
				XMLDB_KEY_FOREIGN,
				['messageid'],
				'local_subscriptions_inbox_message',
				['id']
			);

			$table->add_index(
				'message_provider_uix',
				XMLDB_INDEX_UNIQUE,
				['messageid', 'providerattachmentid']
			);
			$table->add_index(
				'downloadstatus_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['downloadstatus']
			);
			$table->add_index(
				'contenthash_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['contenthash']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_tag'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'name',
				XMLDB_TYPE_CHAR,
				'100',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'colour',
				XMLDB_TYPE_CHAR,
				'20'
			);
			$table->add_field(
				'enabled',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				1
			);
			$table->add_field(
				'createdby',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'createdby_fk',
				XMLDB_KEY_FOREIGN,
				['createdby'],
				'user',
				['id']
			);

			$table->add_index(
				'name_uix',
				XMLDB_INDEX_UNIQUE,
				['name']
			);
			$table->add_index(
				'enabled_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['enabled']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_thread_tag'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'threadid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'tagid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'createdby',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'threadid_fk',
				XMLDB_KEY_FOREIGN,
				['threadid'],
				'local_subscriptions_inbox_thread',
				['id']
			);
			$table->add_key(
				'tagid_fk',
				XMLDB_KEY_FOREIGN,
				['tagid'],
				'local_subscriptions_inbox_tag',
				['id']
			);
			$table->add_key(
				'createdby_fk',
				XMLDB_KEY_FOREIGN,
				['createdby'],
				'user',
				['id']
			);

			$table->add_index(
				'thread_tag_uix',
				XMLDB_INDEX_UNIQUE,
				['threadid', 'tagid']
			);

			$dbman->create_table($table);
		}

		$table = new xmldb_table(
			'local_subscriptions_inbox_sync_log'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$table->add_field(
				'accountid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'synctype',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL
			);
			$table->add_field(
				'folder',
				XMLDB_TYPE_CHAR,
				'255'
			);
			$table->add_field(
				'cursorbefore',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'cursorafter',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'fetchedcount',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'createdcount',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'updatedcount',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'skippedcount',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'errorcount',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'message',
				XMLDB_TYPE_TEXT
			);
			$table->add_field(
				'startedat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$table->add_field(
				'finishedat',
				XMLDB_TYPE_INTEGER,
				'10'
			);
			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$table->add_key(
				'accountid_fk',
				XMLDB_KEY_FOREIGN,
				['accountid'],
				'local_subscriptions_inbox_account',
				['id']
			);
			
			$table->add_index(
				'status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['status']
			);
			$table->add_index(
				'startedat_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['startedat']
			);
			$table->add_index(
				'account_started_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['accountid', 'startedat']
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026071201,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026071203) {
		$table = new xmldb_table('local_subscriptions_inbox_message');

		// 1. Ajouter providerkey comme champ nullable pendant la migration.
		$field = new xmldb_field(
			'providerkey',
			XMLDB_TYPE_CHAR,
			'64',
			null,
			null,
			null,
			null,
			'provideruid'
		);

		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}

		// 2. Générer providerkey pour les messages déjà enregistrés.
		$recordset = $DB->get_recordset(
			'local_subscriptions_inbox_message',
			[
				'providerkey' => null,
			],
			'',
			'id, folder, uidvalidity, provideruid'
		);

		foreach ($recordset as $record) {
			$providerkey = hash(
				'sha256',
				(string)$record->folder
					. "\0"
					. (string)$record->uidvalidity
					. "\0"
					. (string)$record->provideruid
			);

			$DB->set_field(
				'local_subscriptions_inbox_message',
				'providerkey',
				$providerkey,
				['id' => $record->id]
			);
		}

		$recordset->close();

		// 3. Supprimer l'ancien index unique trop long.
		$oldindex = new xmldb_index(
			'provider_uid_uix',
			XMLDB_INDEX_UNIQUE,
			[
				'accountid',
				'folder',
				'uidvalidity',
				'provideruid',
			]
		);

		if ($dbman->index_exists($table, $oldindex)) {
			$dbman->drop_index($table, $oldindex);
		}

		// 4. Rendre providerkey obligatoire après le remplissage des anciennes lignes.
		$field = new xmldb_field(
			'providerkey',
			XMLDB_TYPE_CHAR,
			'64',
			null,
			XMLDB_NOTNULL,
			null,
			null,
			'provideruid'
		);

		$dbman->change_field_notnull($table, $field);

		// 5. Ajouter le nouvel index unique compact.
		$newindex = new xmldb_index(
			'provider_key_uix',
			XMLDB_INDEX_UNIQUE,
			[
				'accountid',
				'providerkey',
			]
		);

		if (!$dbman->index_exists($table, $newindex)) {
			$dbman->add_index($table, $newindex);
		}

		upgrade_plugin_savepoint(
			true,
			2026071203,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026071207) {
		upgrade_plugin_savepoint(
			true,
			2026071207,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026071208) {
		$messagetable = new xmldb_table(
			'local_subscriptions_inbox_message'
		);

		$identityfield = new xmldb_field(
			'identitykey',
			XMLDB_TYPE_CHAR,
			'64',
			null,
			null,
			null,
			null,
			'providerkey'
		);

		if (
			$dbman->table_exists($messagetable) &&
			!$dbman->field_exists(
				$messagetable,
				$identityfield
			)
		) {
			$dbman->add_field(
				$messagetable,
				$identityfield
			);
		}

		$identityindex = new xmldb_index(
			'account_identity_uix',
			XMLDB_INDEX_UNIQUE,
			['accountid', 'identitykey']
		);

		if (
			$dbman->table_exists($messagetable) &&
			!$dbman->index_exists(
				$messagetable,
				$identityindex
			)
		) {
			$dbman->add_index(
				$messagetable,
				$identityindex
			);
		}

		$remotetable = new xmldb_table(
			'local_subscriptions_inbox_remote'
		);

		if (!$dbman->table_exists($remotetable)) {
			$remotetable->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);
			$remotetable->add_field(
				'messageid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$remotetable->add_field(
				'accountid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);
			$remotetable->add_field(
				'folder',
				XMLDB_TYPE_CHAR,
				'191',
				null,
				XMLDB_NOTNULL
			);
			$remotetable->add_field(
				'uidvalidity',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);
			$remotetable->add_field(
				'provideruid',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);
			$remotetable->add_field(
				'providerkey',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);
			$remotetable->add_field(
				'active',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				1
			);
			$remotetable->add_field(
				'firstseenat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$remotetable->add_field(
				'lastseenat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$remotetable->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);
			$remotetable->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$remotetable->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);
			$remotetable->add_key(
				'messageid_fk',
				XMLDB_KEY_FOREIGN,
				['messageid'],
				'local_subscriptions_inbox_message',
				['id']
			);
			$remotetable->add_key(
				'accountid_fk',
				XMLDB_KEY_FOREIGN,
				['accountid'],
				'local_subscriptions_inbox_account',
				['id']
			);

			$remotetable->add_index(
				'account_provider_uix',
				XMLDB_INDEX_UNIQUE,
				['accountid', 'providerkey']
			);
			$remotetable->add_index(
				'active_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['active']
			);
			$remotetable->add_index(
				'folder_active_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['accountid', 'folder', 'active']
			);

			$dbman->create_table($remotetable);
		}

		upgrade_plugin_savepoint(
			true,
			2026071208,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026071209) {
		$table = new xmldb_table(
			'local_subscriptions_inbox_thread'
		);

		$field = new xmldb_field(
			'lastmessageid',
			XMLDB_TYPE_INTEGER,
			'10',
			null,
			null,
			null,
			null,
			'lastmessageat'
		);

		if (
			$dbman->table_exists($table) &&
			!$dbman->field_exists($table, $field)
		) {
			$dbman->add_field(
				$table,
				$field
			);
		}

		$index = new xmldb_index(
			'lastmessageid_idx',
			XMLDB_INDEX_NOTUNIQUE,
			['lastmessageid']
		);

		if (
			!$dbman->index_exists(
				$table,
				$index
			)
		) {
			$dbman->add_index(
				$table,
				$index
			);
		}

		/*
		* Backfill avec le message le plus récent déjà présent.
		*/
		$sql = "
			UPDATE {local_subscriptions_inbox_thread} t
			SET lastmessageid = (
				SELECT MAX(m.id)
					FROM {local_subscriptions_inbox_message} m
					WHERE m.threadid = t.id
			)
		";

		$DB->execute($sql);

		upgrade_plugin_savepoint(
			true,
			2026071209,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026071212) {
		$table = new xmldb_table(
			'local_subscriptions_inbox_ai_result'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);

			$table->add_field(
				'threadid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'messageid',
				XMLDB_TYPE_INTEGER,
				'10'
			);

			$table->add_field(
				'capability',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'provider',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'model',
				XMLDB_TYPE_CHAR,
				'128'
			);

			$table->add_field(
				'promptversion',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'inputhash',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'cachekey',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'32',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'confidence',
				XMLDB_TYPE_NUMBER,
				'10',
				'6',
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_field(
				'datajson',
				XMLDB_TYPE_TEXT
			);

			$table->add_field(
				'warningsjson',
				XMLDB_TYPE_TEXT
			);

			$table->add_field(
				'metadatajson',
				XMLDB_TYPE_TEXT
			);

			$table->add_field(
				'errormessage',
				XMLDB_TYPE_TEXT
			);

			$table->add_field(
				'requestedby',
				XMLDB_TYPE_INTEGER,
				'10'
			);

			$table->add_field(
				'generatedat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_field(
				'expiresat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'threadid_fk',
				XMLDB_KEY_FOREIGN,
				['threadid'],
				'local_subscriptions_inbox_thread',
				['id']
			);

			$table->add_key(
				'messageid_fk',
				XMLDB_KEY_FOREIGN,
				['messageid'],
				'local_subscriptions_inbox_message',
				['id']
			);

			$table->add_key(
				'requestedby_fk',
				XMLDB_KEY_FOREIGN,
				['requestedby'],
				'user',
				['id']
			);

			$table->add_index(
				'cachekey_uix',
				XMLDB_INDEX_UNIQUE,
				['cachekey']
			);

			$table->add_index(
				'thread_capability_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['threadid', 'capability']
			);

			$table->add_index(
				'expiresat_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['expiresat']
			);

			$table->add_index(
				'provider_status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['provider', 'status']
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026071212,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026071216) {
		$table = new xmldb_table(
			'local_subscriptions_inbox_ai_result'
		);

		$fields = [
			new xmldb_field(
				'inputtokens',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0,
				'metadatajson'
			),
			new xmldb_field(
				'outputtokens',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0,
				'inputtokens'
			),
			new xmldb_field(
				'totaltokens',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				0,
				'outputtokens'
			),
			new xmldb_field(
				'requestid',
				XMLDB_TYPE_CHAR,
				'191',
				null,
				null,
				null,
				null,
				'totaltokens'
			),
		];

		foreach ($fields as $field) {
			if (!$dbman->field_exists(
				$table,
				$field
			)) {
				$dbman->add_field(
					$table,
					$field
				);
			}
		}

		upgrade_plugin_savepoint(
			true,
			2026071216,
			'local',
			'subscriptions'
		);
	}

    if ($oldversion < 2026071217) {
        $table = new xmldb_table(
            'local_subscriptions_inbox_ai_result'
        );

        if ($dbman->table_exists($table)) {
            $uniqueindex = new xmldb_index(
                'cachekey_uix',
                XMLDB_INDEX_UNIQUE,
                ['cachekey']
            );

            if (
                $dbman->index_exists(
                    $table,
                    $uniqueindex
                )
            ) {
                $dbman->drop_index(
                    $table,
                    $uniqueindex
                );
            }

            $historyindex = new xmldb_index(
                'cachekey_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['cachekey']
            );

            if (
                !$dbman->index_exists(
                    $table,
                    $historyindex
                )
            ) {
                $dbman->add_index(
                    $table,
                    $historyindex
                );
            }
        }

        upgrade_plugin_savepoint(
            true,
            2026071217,
            'local',
            'subscriptions'
        );
    }

	if ($oldversion < 2026071218) {
		/*
		* Phase 6.75E — Work Management Engine.
		*
		* Creates:
		* - Work teams.
		* - Work team members.
		* - Work items.
		* - Internal comments.
		* - Links to CRM objects.
		* - Immutable Work Item history.
		*/

		/*
		* Work teams.
		*/
		$table = new xmldb_table(
			'local_subscriptions_work_team'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE,
				null
			);

			$table->add_field(
				'name',
				XMLDB_TYPE_CHAR,
				'191',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'description',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'enabled',
				XMLDB_TYPE_INTEGER,
				'1',
				null,
				XMLDB_NOTNULL,
				null,
				'1'
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_index(
				'name_uix',
				XMLDB_INDEX_UNIQUE,
				['name']
			);

			$table->add_index(
				'enabled_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['enabled']
			);

			$dbman->create_table($table);
		}

		/*
		* Work team members.
		*/
		$table = new xmldb_table(
			'local_subscriptions_work_team_member'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE,
				null
			);

			$table->add_field(
				'teamid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'userid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'role',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'member'
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'teamid_fk',
				XMLDB_KEY_FOREIGN,
				['teamid'],
				'local_subscriptions_work_team',
				['id']
			);

			$table->add_key(
				'userid_fk',
				XMLDB_KEY_FOREIGN,
				['userid'],
				'user',
				['id']
			);

			/*
			* Do not add a separate index on userid:
			* userid_fk already provides it.
			*/
			$table->add_index(
				'team_user_uix',
				XMLDB_INDEX_UNIQUE,
				['teamid', 'userid']
			);

			$dbman->create_table($table);
		}

		/*
		* Work items.
		*/
		$table = new xmldb_table(
			'local_subscriptions_work_item'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE,
				null
			);

			$table->add_field(
				'reference',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'type',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'task'
			);

			$table->add_field(
				'title',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'description',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'open'
			);

			$table->add_field(
				'priority',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				'normal'
			);

			$table->add_field(
				'source',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'manual'
			);

			$table->add_field(
				'targetuserid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'assigneduserid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'assignedteamid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'parentid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'createdby',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'dueat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'resolvedat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'closedat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'targetuserid_fk',
				XMLDB_KEY_FOREIGN,
				['targetuserid'],
				'user',
				['id']
			);

			$table->add_key(
				'assigneduserid_fk',
				XMLDB_KEY_FOREIGN,
				['assigneduserid'],
				'user',
				['id']
			);

			$table->add_key(
				'assignedteamid_fk',
				XMLDB_KEY_FOREIGN,
				['assignedteamid'],
				'local_subscriptions_work_team',
				['id']
			);

			$table->add_key(
				'parentid_fk',
				XMLDB_KEY_FOREIGN,
				['parentid'],
				'local_subscriptions_work_item',
				['id']
			);

			$table->add_key(
				'createdby_fk',
				XMLDB_KEY_FOREIGN,
				['createdby'],
				'user',
				['id']
			);

			$table->add_index(
				'reference_uix',
				XMLDB_INDEX_UNIQUE,
				['reference']
			);

			$table->add_index(
				'status_priority_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['status', 'priority']
			);

			$table->add_index(
				'assignee_status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['assigneduserid', 'status']
			);

			$table->add_index(
				'team_status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['assignedteamid', 'status']
			);

			$table->add_index(
				'target_status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['targetuserid', 'status']
			);

			$table->add_index(
				'due_status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['dueat', 'status']
			);

			/*
			* Do not add a separate parentid index:
			* parentid_fk already provides it.
			*/
			$dbman->create_table($table);
		}

		/*
		* Internal Work Item comments.
		*/
		$table = new xmldb_table(
			'local_subscriptions_work_comment'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE,
				null
			);

			$table->add_field(
				'itemid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'authorid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'body',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'visibility',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				'internal'
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'itemid_fk',
				XMLDB_KEY_FOREIGN,
				['itemid'],
				'local_subscriptions_work_item',
				['id']
			);

			$table->add_key(
				'authorid_fk',
				XMLDB_KEY_FOREIGN,
				['authorid'],
				'user',
				['id']
			);

			$table->add_index(
				'item_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['itemid', 'timecreated']
			);

			$dbman->create_table($table);
		}

		/*
		* Links between Work Items and CRM objects.
		*/
		$table = new xmldb_table(
			'local_subscriptions_work_link'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE,
				null
			);

			$table->add_field(
				'itemid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'objecttype',
				XMLDB_TYPE_CHAR,
				'50',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'objectid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'relation',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'related'
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'itemid_fk',
				XMLDB_KEY_FOREIGN,
				['itemid'],
				'local_subscriptions_work_item',
				['id']
			);

			$table->add_index(
				'item_object_relation_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'itemid',
					'objecttype',
					'objectid',
					'relation',
				]
			);

			$table->add_index(
				'object_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['objecttype', 'objectid']
			);

			$dbman->create_table($table);
		}

		/*
		* Immutable Work Item history.
		*/
		$table = new xmldb_table(
			'local_subscriptions_work_history'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE,
				null
			);

			$table->add_field(
				'itemid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'actorid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'action',
				XMLDB_TYPE_CHAR,
				'50',
				null,
				XMLDB_NOTNULL,
				null,
				null
			);

			$table->add_field(
				'oldvalue',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'newvalue',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'metadatajson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null,
				null,
				null
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'itemid_fk',
				XMLDB_KEY_FOREIGN,
				['itemid'],
				'local_subscriptions_work_item',
				['id']
			);

			$table->add_key(
				'actorid_fk',
				XMLDB_KEY_FOREIGN,
				['actorid'],
				'user',
				['id']
			);

			$table->add_index(
				'item_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['itemid', 'timecreated']
			);

			$table->add_index(
				'action_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['action']
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026071218,
			'local',
			'subscriptions'
		);
	}

    if ($oldversion < 2026071601) {
        /*
         * Persistent CRM recommendations.
         */
        $table = new xmldb_table(
            'local_subscriptions_recommendation'
        );

        if (!$dbman->table_exists($table)) {
            $table->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE,
                null
            );

            $table->add_field(
                'fingerprint',
                XMLDB_TYPE_CHAR,
                '64',
                null,
                XMLDB_NOTNULL,
                null,
                null
            );

            $table->add_field(
                'recommendationkey',
                XMLDB_TYPE_CHAR,
                '100',
                null,
                XMLDB_NOTNULL,
                null,
                null
            );

            $table->add_field(
                'recommendationtype',
                XMLDB_TYPE_CHAR,
                '50',
                null,
                XMLDB_NOTNULL,
                null,
                null
            );

            $table->add_field(
                'presentationtype',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'info'
            );

            $table->add_field(
                'priority',
                XMLDB_TYPE_INTEGER,
                '3',
                null,
                XMLDB_NOTNULL,
                null,
                '50'
            );

            $table->add_field(
                'prioritylevel',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'normal'
            );

            $table->add_field(
                'status',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'proposed'
            );

            $table->add_field(
                'targettype',
                XMLDB_TYPE_CHAR,
                '50',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'targetid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'sourcesjson',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'evidencejson',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'actionsjson',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'generatedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'validuntil',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'firstdetectedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'lastdetectedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'acceptedby',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'acceptedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'dismissedby',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'dismissedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'completedby',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'completedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'dismissalreason',
                XMLDB_TYPE_CHAR,
                '100',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'timecreated',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'timemodified',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );

            $table->add_key(
                'acceptedby_fk',
                XMLDB_KEY_FOREIGN,
                ['acceptedby'],
                'user',
                ['id']
            );

            $table->add_key(
                'dismissedby_fk',
                XMLDB_KEY_FOREIGN,
                ['dismissedby'],
                'user',
                ['id']
            );

            $table->add_key(
                'completedby_fk',
                XMLDB_KEY_FOREIGN,
                ['completedby'],
                'user',
                ['id']
            );

            $table->add_index(
                'fingerprint_uix',
                XMLDB_INDEX_UNIQUE,
                ['fingerprint']
            );

            $table->add_index(
                'status_priority_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['status', 'priority']
            );

            $table->add_index(
                'target_status_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'targettype',
                    'targetid',
                    'status',
                ]
            );

            $table->add_index(
                'type_status_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'recommendationtype',
                    'status',
                ]
            );

            $table->add_index(
                'validuntil_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['validuntil']
            );

            $table->add_index(
                'lastdetected_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['lastdetectedat']
            );

            $dbman->create_table($table);
        }

        /*
         * Immutable recommendation lifecycle history.
         */
        $table = new xmldb_table(
            'local_subscriptions_recommendation_history'
        );

        if (!$dbman->table_exists($table)) {
            $table->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE,
                null
            );

            $table->add_field(
                'recommendationid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                null
            );

            $table->add_field(
                'actorid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'action',
                XMLDB_TYPE_CHAR,
                '50',
                null,
                XMLDB_NOTNULL,
                null,
                null
            );

            $table->add_field(
                'oldstatus',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'newstatus',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'metadatajson',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'timecreated',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );

            $table->add_key(
                'recommendationid_fk',
                XMLDB_KEY_FOREIGN,
                ['recommendationid'],
                'local_subscriptions_recommendation',
                ['id']
            );

            $table->add_key(
                'actorid_fk',
                XMLDB_KEY_FOREIGN,
                ['actorid'],
                'user',
                ['id']
            );

            $table->add_index(
                'recommendation_time_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'recommendationid',
                    'timecreated',
                ]
            );

            $table->add_index(
                'action_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['action']
            );

            $table->add_index(
                'actor_time_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'actorid',
                    'timecreated',
                ]
            );

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(
            true,
            2026071601,
            'local',
            'subscriptions'
        );
    }

    if ($oldversion < 2026071602) {
        /*
         * New capability:
         * local/subscriptions:use_crm_assistant_ai
         *
         * No database structure change is required.
         */
        upgrade_plugin_savepoint(
            true,
            2026071602,
            'local',
            'subscriptions'
        );
    }

    if ($oldversion < 2026071603) {
        $table = new xmldb_table(
            'local_subscriptions_recommendation_run'
        );

        if (!$dbman->table_exists($table)) {
            $table->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE,
                null
            );

            $table->add_field(
                'status',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'running'
            );

            $table->add_field(
                'source',
                XMLDB_TYPE_CHAR,
                '30',
                null,
                XMLDB_NOTNULL,
                null,
                'scheduled_task'
            );

            $table->add_field(
                'startcursor',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'endcursor',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'wrapped',
                XMLDB_TYPE_INTEGER,
                '1',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'requestedlimit',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            foreach ([
                'processedcount',
                'successcount',
                'failedcount',
                'generatedcount',
                'persistedcount',
                'duplicatecount',
                'correlationcount',
                'expiredcount',
                'durationms',
            ] as $fieldname) {
                $table->add_field(
                    $fieldname,
                    XMLDB_TYPE_INTEGER,
                    '10',
                    null,
                    XMLDB_NOTNULL,
                    null,
                    '0'
                );
            }

            $table->add_field(
                'failurejson',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'startedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'finishedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null
            );

            $table->add_field(
                'timecreated',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_field(
                'timemodified',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '0'
            );

            $table->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );

            $table->add_index(
                'status_started_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'status',
                    'startedat',
                ]
            );

            $table->add_index(
                'source_started_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'source',
                    'startedat',
                ]
            );

            $table->add_index(
                'finishedat_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['finishedat']
            );

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(
            true,
            2026071603,
            'local',
            'subscriptions'
        );
    }

    if ($oldversion < 2026071700) {
        $plantable = new xmldb_table(
            'local_subscriptions_cs_plan'
        );

        if (!$dbman->table_exists($plantable)) {
            $plantable->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE
            );

            $plantable->add_field(
                'reference',
                XMLDB_TYPE_CHAR,
                '32',
                null,
                XMLDB_NOTNULL
            );

            $plantable->add_field(
                'userid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL
            );

            $plantable->add_field(
                'objectivekey',
                XMLDB_TYPE_CHAR,
                '100',
                null,
                XMLDB_NOTNULL
            );

            $plantable->add_field(
                'title',
                XMLDB_TYPE_CHAR,
                '255',
                null,
                XMLDB_NOTNULL
            );

            $plantable->add_field(
                'description',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null
            );

            $plantable->add_field(
                'status',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'draft'
            );

            $plantable->add_field(
                'source',
                XMLDB_TYPE_CHAR,
                '30',
                null,
                XMLDB_NOTNULL,
                null,
                'manual'
            );

            $plantable->add_field(
                'priority',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'normal'
            );

            foreach ([
                'assignedteamid',
                'assigneduserid',
                'targetdate',
            ] as $fieldname) {
                $plantable->add_field(
                    $fieldname,
                    XMLDB_TYPE_INTEGER,
                    '10',
                    null,
                    null
                );
            }

            $plantable->add_field(
                'blockedreason',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null
            );

            $plantable->add_field(
                'fingerprint',
                XMLDB_TYPE_CHAR,
                '64',
                null,
                null
            );

            foreach ([
                'activatedat',
                'completedat',
            ] as $fieldname) {
                $plantable->add_field(
                    $fieldname,
                    XMLDB_TYPE_INTEGER,
                    '10',
                    null,
                    null
                );
            }

            foreach ([
                'createdby',
                'modifiedby',
                'timecreated',
                'timemodified',
            ] as $fieldname) {
                $plantable->add_field(
                    $fieldname,
                    XMLDB_TYPE_INTEGER,
                    '10',
                    null,
                    XMLDB_NOTNULL,
                    null,
                    '0'
                );
            }

            $plantable->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );

            $plantable->add_key(
                'userid_fk',
                XMLDB_KEY_FOREIGN,
                ['userid'],
                'user',
                ['id']
            );

            $plantable->add_index(
                'reference_uix',
                XMLDB_INDEX_UNIQUE,
                ['reference']
            );

            $plantable->add_index(
                'userid_status_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'userid',
                    'status',
                ]
            );

            $plantable->add_index(
                'status_priority_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'status',
                    'priority',
                ]
            );

            $plantable->add_index(
                'fingerprint_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['fingerprint']
            );

            $plantable->add_index(
                'assignedteam_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'assignedteamid',
                    'status',
                ]
            );

            $plantable->add_index(
                'assigneduser_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'assigneduserid',
                    'status',
                ]
            );

            $plantable->add_index(
                'targetdate_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['targetdate']
            );

            $dbman->create_table(
                $plantable
            );
        }

        $steptable = new xmldb_table(
            'local_subscriptions_cs_step'
        );

        if (!$dbman->table_exists($steptable)) {
            $steptable->add_field(
                'id',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                XMLDB_SEQUENCE
            );

            $steptable->add_field(
                'planid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL
            );

            $steptable->add_field(
                'position',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                XMLDB_NOTNULL,
                null,
                '1'
            );

            $steptable->add_field(
                'stepkey',
                XMLDB_TYPE_CHAR,
                '100',
                null,
                XMLDB_NOTNULL
            );

            $steptable->add_field(
                'title',
                XMLDB_TYPE_CHAR,
                '255',
                null,
                XMLDB_NOTNULL
            );

            $steptable->add_field(
                'description',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null
            );

            $steptable->add_field(
                'status',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'pending'
            );

            $steptable->add_field(
                'priority',
                XMLDB_TYPE_CHAR,
                '20',
                null,
                XMLDB_NOTNULL,
                null,
                'normal'
            );

            $steptable->add_field(
                'dependsonstepid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null
            );

            $steptable->add_field(
                'blockedreason',
                XMLDB_TYPE_TEXT,
                null,
                null,
                null
            );

            $steptable->add_field(
                'relationtype',
                XMLDB_TYPE_CHAR,
                '30',
                null,
                null
            );

            $steptable->add_field(
                'relationid',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null
            );

            foreach ([
                'assignedteamid',
                'assigneduserid',
                'dueat',
                'startedat',
                'completedat',
            ] as $fieldname) {
                $steptable->add_field(
                    $fieldname,
                    XMLDB_TYPE_INTEGER,
                    '10',
                    null,
                    null
                );
            }

            foreach ([
                'createdby',
                'modifiedby',
                'timecreated',
                'timemodified',
            ] as $fieldname) {
                $steptable->add_field(
                    $fieldname,
                    XMLDB_TYPE_INTEGER,
                    '10',
                    null,
                    XMLDB_NOTNULL,
                    null,
                    '0'
                );
            }

            $steptable->add_key(
                'primary',
                XMLDB_KEY_PRIMARY,
                ['id']
            );

            $steptable->add_key(
                'planid_fk',
                XMLDB_KEY_FOREIGN,
                ['planid'],
                'local_subscriptions_cs_plan',
                ['id']
            );

            $steptable->add_index(
                'plan_position_uix',
                XMLDB_INDEX_UNIQUE,
                [
                    'planid',
                    'position',
                ]
            );

            $steptable->add_index(
                'plan_stepkey_uix',
                XMLDB_INDEX_UNIQUE,
                [
                    'planid',
                    'stepkey',
                ]
            );

            $steptable->add_index(
                'plan_status_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'planid',
                    'status',
                ]
            );

            $steptable->add_index(
                'dependency_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['dependsonstepid']
            );

            $steptable->add_index(
                'relation_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'relationtype',
                    'relationid',
                ]
            );

            $steptable->add_index(
                'assignedteam_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'assignedteamid',
                    'status',
                ]
            );

            $steptable->add_index(
                'assigneduser_idx',
                XMLDB_INDEX_NOTUNIQUE,
                [
                    'assigneduserid',
                    'status',
                ]
            );

            $steptable->add_index(
                'dueat_idx',
                XMLDB_INDEX_NOTUNIQUE,
                ['dueat']
            );

            $dbman->create_table(
                $steptable
            );
        }

        upgrade_plugin_savepoint(
            true,
            2026071700,
            'local',
            'subscriptions'
        );
    }

	if ($oldversion < 2026071702) {
		$table = new xmldb_table(
			'local_subscriptions_cs_relation'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);

			$table->add_field(
				'planid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'stepid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'objecttype',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'objectid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'relation',
				XMLDB_TYPE_CHAR,
				'30',
				null,
				XMLDB_NOTNULL,
				null,
				'related'
			);

			$table->add_field(
				'createdby',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'planid_fk',
				XMLDB_KEY_FOREIGN,
				['planid'],
				'local_subscriptions_cs_plan',
				['id']
			);

			$table->add_key(
				'stepid_fk',
				XMLDB_KEY_FOREIGN,
				['stepid'],
				'local_subscriptions_cs_step',
				['id']
			);

			$table->add_index(
				'step_object_relation_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'stepid',
					'objecttype',
					'objectid',
					'relation',
				]
			);

			$table->add_index(
				'plan_object_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'planid',
					'objecttype',
					'objectid',
				]
			);

			$table->add_index(
				'object_lookup_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'objecttype',
					'objectid',
				]
			);

			$dbman->create_table($table);
		}

		/*
		* Migrate the primary recommendation relations created by 7.8C.
		*/
		if (
			$DB->get_manager()->table_exists(
				new xmldb_table(
					'local_subscriptions_cs_step'
				)
			)
		) {
			$sql = "
				SELECT s.id,
					s.planid,
					s.relationtype,
					s.relationid,
					s.createdby,
					s.timecreated
				FROM {local_subscriptions_cs_step} s
				WHERE s.relationtype IS NOT NULL
				AND s.relationid IS NOT NULL
			";

			$records = $DB->get_recordset_sql($sql);

			foreach ($records as $record) {
				if (
					!$DB->record_exists(
						'local_subscriptions_cs_relation',
						[
							'stepid' =>
								(int)$record->id,
							'objecttype' =>
								(string)$record->relationtype,
							'objectid' =>
								(int)$record->relationid,
							'relation' =>
								'created_from',
						]
					)
				) {
					$DB->insert_record(
						'local_subscriptions_cs_relation',
						(object)[
							'planid' =>
								(int)$record->planid,
							'stepid' =>
								(int)$record->id,
							'objecttype' =>
								(string)$record->relationtype,
							'objectid' =>
								(int)$record->relationid,
							'relation' =>
								'created_from',
							'createdby' =>
								(int)$record->createdby,
							'timecreated' =>
								(int)$record->timecreated,
						]
					);
				}
			}

			$records->close();
		}

		upgrade_plugin_savepoint(
			true,
			2026071702,
			'local',
			'subscriptions'
		);
	}	

	if ($oldversion < 2026071703) {
		$plantable =
			'local_subscriptions_cs_plan';

		if (
			$dbman->table_exists(
				new xmldb_table(
					$plantable
				)
			)
		) {
			$plans =
				$DB->get_records_select(
					$plantable,
					'source <> :manualsource',
					[
						'manualsource' => 'manual',
					],
					'',
					'id, objectivekey'
				);

			$validobjectives = [
				'reduce_churn_risk',
				'resolve_payment_friction',
				'resolve_support_pressure',
				'restore_learning_access',
				'restore_learning_engagement',
				'develop_customer_opportunity',
				'coordinate_customer_success',
			];

			foreach ($plans as $plan) {
				$objectivekey =
					in_array(
						(string)$plan->objectivekey,
						$validobjectives,
						true
					)
						? (string)$plan->objectivekey
						: 'coordinate_customer_success';

				$recommendationcount =
					$DB->count_records(
						'local_subscriptions_cs_step',
						[
							'planid' =>
								(int)$plan->id,
						]
					);

				$DB->update_record(
					$plantable,
					(object)[
						'id' =>
							(int)$plan->id,

						'title' =>
							'[[csplan-objective:' .
							$objectivekey .
							']]',

						'description' =>
							'[[csplan-description:recommendations:' .
							$recommendationcount .
							']]',

						'timemodified' =>
							time(),
					]
				);
			}
		}

		upgrade_plugin_savepoint(
			true,
			2026071703,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026071803) {
		$table = new xmldb_table(
			'subscription_payment_request'
		);

		if ($dbman->table_exists($table)) {
			/*
			* Ensure that the real last_update field exists.
			*
			* install.xml already contains it, but an old upgrade
			* accidentally created a field named after the table.
			*/
			$lastupdatefield = new xmldb_field(
				'last_update',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0',
				'creation_date'
			);

			if (
				!$dbman->field_exists(
					$table,
					$lastupdatefield
				)
			) {
				$dbman->add_field(
					$table,
					$lastupdatefield
				);
			}

			/*
			* Remove the field accidentally created by upgrade 2025082800.
			*/
			$invalidfield = new xmldb_field(
				'subscription_payment_request'
			);

			if (
				$dbman->field_exists(
					$table,
					$invalidfield
				)
			) {
				$dbman->drop_field(
					$table,
					$invalidfield
				);
			}

			/*
			* Backfill only empty technical timestamps.
			*/
			$DB->execute(
				"
					UPDATE {subscription_payment_request}
					SET last_update = CASE
						WHEN payment_date IS NOT NULL
								AND payment_date > 0
							THEN payment_date
						WHEN last_attempt IS NOT NULL
								AND last_attempt > 0
							THEN last_attempt
						ELSE creation_date
					END
					WHERE last_update IS NULL
						OR last_update = 0
				"
			);
		}

		upgrade_plugin_savepoint(
			true,
			2026071803,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026072000) {
		$table = new xmldb_table(
			'local_subscriptions_admin_tool_run'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);

			$table->add_field(
				'toolkey',
				XMLDB_TYPE_CHAR,
				'80',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'actorid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				'running'
			);

			$table->add_field(
				'risklevel',
				XMLDB_TYPE_CHAR,
				'20',
				null,
				XMLDB_NOTNULL,
				null,
				'normal'
			);

			$table->add_field(
				'requestid',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'parametersjson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null
			);

			$table->add_field(
				'resultjson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null
			);

			$table->add_field(
				'errormessage',
				XMLDB_TYPE_TEXT,
				null,
				null,
				null
			);

			$table->add_field(
				'startedat',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'finishedat',
				XMLDB_TYPE_INTEGER,
				'10',
				null
			);

			$table->add_field(
				'durationms',
				XMLDB_TYPE_INTEGER,
				'10',
				null
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'actorid_fk',
				XMLDB_KEY_FOREIGN,
				['actorid'],
				'user',
				['id']
			);

			$table->add_index(
				'requestid_uix',
				XMLDB_INDEX_UNIQUE,
				['requestid']
			);

			$table->add_index(
				'tool_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'toolkey',
					'timecreated',
				]
			);

			$table->add_index(
				'actor_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'actorid',
					'timecreated',
				]
			);

			$table->add_index(
				'status_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'status',
					'timecreated',
				]
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026072000,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026072100) {
		$table = new xmldb_table(
			'local_subscriptions_inbox_message'
		);

		$providerkeyfield = new xmldb_field(
			'providerkey',
			XMLDB_TYPE_CHAR,
			'64',
			null,
			null,
			null,
			null,
			'provideruid'
		);

		$providerkeyindex = new xmldb_index(
			'provider_key_uix',
			XMLDB_INDEX_UNIQUE,
			[
				'accountid',
				'providerkey',
			]
		);

		/*
		* XMLDB cannot alter a field while an index depends on it.
		* Temporarily remove the unique provider index.
		*/
		if (
			$dbman->table_exists($table)
			&& $dbman->index_exists(
				$table,
				$providerkeyindex
			)
		) {
			$dbman->drop_index(
				$table,
				$providerkeyindex
			);
		}

		/*
		* Drafts created locally do not yet have a remote provider key.
		* The field must therefore allow NULL.
		*/
		if (
			$dbman->table_exists($table)
			&& $dbman->field_exists(
				$table,
				$providerkeyfield
			)
		) {
			$dbman->change_field_notnull(
				$table,
				$providerkeyfield
			);
		}

		/*
		* Restore the unique index after changing the field.
		*/
		if (
			$dbman->table_exists($table)
			&& !$dbman->index_exists(
				$table,
				$providerkeyindex
			)
		) {
			$dbman->add_index(
				$table,
				$providerkeyindex
			);
		}

		upgrade_plugin_savepoint(
			true,
			2026072100,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026072400) {
		$table = new xmldb_table(
			'local_subscriptions_commerce_purchase'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);

			$table->add_field(
				'purchaseuuid',
				XMLDB_TYPE_CHAR,
				'32',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'reference',
				XMLDB_TYPE_CHAR,
				'28',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'type',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'legacyfamily',
				XMLDB_TYPE_CHAR,
				'32'
			);

			$table->add_field(
				'legacyid',
				XMLDB_TYPE_INTEGER,
				'10'
			);

			$table->add_field(
				'userid',
				XMLDB_TYPE_INTEGER,
				'10'
			);

			$table->add_field(
				'customeremail',
				XMLDB_TYPE_CHAR,
				'254'
			);

			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'32',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'currency',
				XMLDB_TYPE_CHAR,
				'3',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'subtotalminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'discountminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'totalminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'customerjson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'snapshotjson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'metadatajson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'snapshotversion',
				XMLDB_TYPE_INTEGER,
				'4',
				null,
				XMLDB_NOTNULL,
				null,
				'1'
			);

			$table->add_field(
				'timecreated',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'timemodified',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_index(
				'purchaseuuid_uix',
				XMLDB_INDEX_UNIQUE,
				['purchaseuuid']
			);

			$table->add_index(
				'reference_uix',
				XMLDB_INDEX_UNIQUE,
				['reference']
			);

			$table->add_index(
				'legacy_reference_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'legacyfamily',
					'legacyid',
				]
			);

			$table->add_index(
				'userid_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'userid',
					'timecreated',
				]
			);

			$table->add_index(
				'status_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'status',
					'timemodified',
				]
			);

			$table->add_index(
				'type_time_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'type',
					'timecreated',
				]
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026072400,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026072401) {
		$table = new xmldb_table(
			'local_subscriptions_commerce_purchase_item'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);

			$table->add_field(
				'purchaseid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'position',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'itemtype',
				XMLDB_TYPE_CHAR,
				'64',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'itemreference',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'label',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'quantity',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'1'
			);

			$table->add_field(
				'currency',
				XMLDB_TYPE_CHAR,
				'3',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'unitminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'grossminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'discountminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'netminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'pricingjson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'fulfillmentjson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'metadatajson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'purchase_fk',
				XMLDB_KEY_FOREIGN,
				['purchaseid'],
				'local_subscriptions_commerce_purchase',
				['id']
			);

			$table->add_index(
				'purchase_position_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'purchaseid',
					'position',
				]
			);

			$table->add_index(
				'item_reference_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'itemtype',
					'itemreference',
				]
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026072401,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026072402) {
		$table = new xmldb_table(
			'local_subscriptions_commerce_payment'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);

			$table->add_field(
				'purchaseid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'sequence',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'provider',
				XMLDB_TYPE_CHAR,
				'64'
			);

			$table->add_field(
				'providerreference',
				XMLDB_TYPE_CHAR,
				'255'
			);

			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'32',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'currency',
				XMLDB_TYPE_CHAR,
				'3',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'amountminor',
				XMLDB_TYPE_INTEGER,
				'18',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'transactionid',
				XMLDB_TYPE_CHAR,
				'255'
			);

			$table->add_field(
				'legacyrequestid',
				XMLDB_TYPE_INTEGER,
				'10'
			);

			$table->add_field(
				'paidat',
				XMLDB_TYPE_INTEGER,
				'10'
			);

			$table->add_field(
				'metadatajson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'purchase_fk',
				XMLDB_KEY_FOREIGN,
				['purchaseid'],
				'local_subscriptions_commerce_purchase',
				['id']
			);

			$table->add_index(
				'purchase_sequence_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'purchaseid',
					'sequence',
				]
			);

			$table->add_index(
				'provider_reference_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'provider',
					'providerreference',
				]
			);

			$table->add_index(
				'provider_transaction_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'provider',
					'transactionid',
				]
			);

			$table->add_index(
				'status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['status']
			);

			$table->add_index(
				'legacy_request_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['legacyrequestid']
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026072402,
			'local',
			'subscriptions'
		);
	}

	if ($oldversion < 2026072403) {
		$table = new xmldb_table(
			'local_subscriptions_commerce_fulfillment'
		);

		if (!$dbman->table_exists($table)) {
			$table->add_field(
				'id',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				XMLDB_SEQUENCE
			);

			$table->add_field(
				'purchaseid',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'sequence',
				XMLDB_TYPE_INTEGER,
				'10',
				null,
				XMLDB_NOTNULL,
				null,
				'0'
			);

			$table->add_field(
				'reference',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'fulfillmentkey',
				XMLDB_TYPE_CHAR,
				'128',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'idempotencykey',
				XMLDB_TYPE_CHAR,
				'255',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'status',
				XMLDB_TYPE_CHAR,
				'32',
				null,
				XMLDB_NOTNULL
			);

			$table->add_field(
				'metadatajson',
				XMLDB_TYPE_TEXT,
				null,
				null,
				XMLDB_NOTNULL
			);

			$table->add_key(
				'primary',
				XMLDB_KEY_PRIMARY,
				['id']
			);

			$table->add_key(
				'purchase_fk',
				XMLDB_KEY_FOREIGN,
				['purchaseid'],
				'local_subscriptions_commerce_purchase',
				['id']
			);

			$table->add_index(
				'purchase_sequence_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'purchaseid',
					'sequence',
				]
			);

			$table->add_index(
				'idempotencykey_uix',
				XMLDB_INDEX_UNIQUE,
				['idempotencykey']
			);

			$table->add_index(
				'purchase_reference_uix',
				XMLDB_INDEX_UNIQUE,
				[
					'purchaseid',
					'reference',
				]
			);

			$table->add_index(
				'fulfillmentkey_status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				[
					'fulfillmentkey',
					'status',
				]
			);

			$table->add_index(
				'status_idx',
				XMLDB_INDEX_NOTUNIQUE,
				['status']
			);

			$dbman->create_table($table);
		}

		upgrade_plugin_savepoint(
			true,
			2026072403,
			'local',
			'subscriptions'
		);
	}


    if ($oldversion < 2026072504) {
        $table = new xmldb_table('local_subs_commerce_cron_run');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('jobname', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
            $table->add_field('countersjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('warningsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('errorsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('startedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('finishedat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('durationms', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('peakmemorybytes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('dbqueries', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('jobname_finishedat_idx', XMLDB_INDEX_NOTUNIQUE, ['jobname', 'finishedat']);
            $table->add_index('status_timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'timecreated']);
            $table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026072504, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072512) {
        $table = new xmldb_table('local_subs_commerce_idem');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('scope', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $table->add_field('idempotencykey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('payloadhash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $table->add_field('status', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
            $table->add_field('resultjson', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('errormessage', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('lockeduntil', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('scope_key_uix', XMLDB_INDEX_UNIQUE, ['scope', 'idempotencykey']);
            $table->add_index('status_lockeduntil_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'lockeduntil']);
            $table->add_index('timemodified_idx', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026072512, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072517) {
        $canonical = get_config('local_subscriptions', 'commerce_native_dual_write_enabled');
        $legacy = get_config('local_subscriptions', 'commerce_dual_write_enabled');

        if (empty($canonical) && !empty($legacy)) {
            set_config('commerce_native_dual_write_enabled', 1, 'local_subscriptions');
        }

        upgrade_plugin_savepoint(true, 2026072517, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072520) {
        upgrade_plugin_savepoint(true, 2026072520, 'local', 'subscriptions');
    }

    if ($oldversion < 2026072521) {
        upgrade_plugin_savepoint(true, 2026072521, 'local', 'subscriptions');
    }

    if ($oldversion < 2026072522) {
        upgrade_plugin_savepoint(true, 2026072522, 'local', 'subscriptions');
    }

    if ($oldversion < 2026072523) {
        upgrade_plugin_savepoint(true, 2026072523, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072525) {
        $tables = [];

        $table = new xmldb_table('local_subs_commerce_product');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('sku', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('type', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('availablefrom', XMLDB_TYPE_INTEGER, '10', null, null);
        $table->add_field('availableuntil', XMLDB_TYPE_INTEGER, '10', null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('sku_uix', XMLDB_INDEX_UNIQUE, ['sku']);
        $table->add_index('status_availability_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'availablefrom', 'availableuntil']);
        $table->add_index('type_status_idx', XMLDB_INDEX_NOTUNIQUE, ['type', 'status']);
        $tables[] = $table;

        $table = new xmldb_table('local_subs_commerce_prod_price');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('productid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('currency', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL);
        $table->add_field('amountminor', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL);
        $table->add_field('provider', XMLDB_TYPE_CHAR, '64', null, null);
        $table->add_field('providerpriceid', XMLDB_TYPE_CHAR, '255', null, null);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('product_fk', XMLDB_KEY_FOREIGN, ['productid'], 'local_subs_commerce_product', ['id']);
        $table->add_index('product_currency_provider_uix', XMLDB_INDEX_UNIQUE, ['productid', 'currency', 'provider']);
        $table->add_index('active_currency_idx', XMLDB_INDEX_NOTUNIQUE, ['active', 'currency']);
        $tables[] = $table;

        $specs = [
            'local_subs_commerce_prod_tr' => function(xmldb_table $table): void {
                $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
                $table->add_field('productid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('language', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
                $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
                $table->add_field('shortdescription', XMLDB_TYPE_TEXT, null, null, null);
                $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
                $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, null);
                $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
                $table->add_key('product_fk', XMLDB_KEY_FOREIGN, ['productid'], 'local_subs_commerce_product', ['id']);
                $table->add_index('product_language_uix', XMLDB_INDEX_UNIQUE, ['productid', 'language']);
            },
            'local_subs_commerce_prod_comp' => function(xmldb_table $table): void {
                $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
                $table->add_field('parentproductid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('childproductid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('quantity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
                $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, null);
                $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
                $table->add_key('parent_fk', XMLDB_KEY_FOREIGN, ['parentproductid'], 'local_subs_commerce_product', ['id']);
                $table->add_key('child_fk', XMLDB_KEY_FOREIGN, ['childproductid'], 'local_subs_commerce_product', ['id']);
                $table->add_index('parent_child_uix', XMLDB_INDEX_UNIQUE, ['parentproductid', 'childproductid']);
                $table->add_index('parent_sortorder_idx', XMLDB_INDEX_NOTUNIQUE, ['parentproductid', 'sortorder']);
            },
            'local_subs_commerce_prod_ent' => function(xmldb_table $table): void {
                $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
                $table->add_field('productid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('type', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
                $table->add_field('resourcekey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
                $table->add_field('durationseconds', XMLDB_TYPE_INTEGER, '10', null, null);
                $table->add_field('quantity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
                $table->add_field('configurationjson', XMLDB_TYPE_TEXT, null, null, null);
                $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
                $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
                $table->add_key('product_fk', XMLDB_KEY_FOREIGN, ['productid'], 'local_subs_commerce_product', ['id']);
                $table->add_index('product_definition_uix', XMLDB_INDEX_UNIQUE, ['productid', 'type', 'resourcekey']);
                $table->add_index('product_sortorder_idx', XMLDB_INDEX_NOTUNIQUE, ['productid', 'sortorder']);
            },
            'local_subs_commerce_prod_map' => function(xmldb_table $table): void {
                $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
                $table->add_field('productid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('legacyfamily', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
                $table->add_field('legacytable', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
                $table->add_field('legacyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
                $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
                $table->add_key('product_fk', XMLDB_KEY_FOREIGN, ['productid'], 'local_subs_commerce_product', ['id']);
                $table->add_index('legacy_source_uix', XMLDB_INDEX_UNIQUE, ['legacytable', 'legacyid']);
                $table->add_index('product_family_uix', XMLDB_INDEX_UNIQUE, ['productid', 'legacyfamily']);
            },
        ];

        foreach ($specs as $tablename => $build) {
            $table = new xmldb_table($tablename);
            $build($table);
            $tables[] = $table;
        }

        foreach ($tables as $table) {
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        upgrade_plugin_savepoint(true, 2026072525, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072526) {
        /*
         * Phase 7.94B schema normalisation.
         *
         * Align upgraded installations with the canonical install.xml schema.
         */

        // Legacy catalogue foreign-key field names.
        $renames = [
            ['subscription_plan_price', 'plan_id', 'planid'],
            ['subscription_plan_translation', 'plan_id', 'planid'],
            ['subscription_access_scope_translation', 'scope_id', 'accessscopeid'],
        ];

        foreach ($renames as [$tablename, $oldfieldname, $newfieldname]) {
            $table = new xmldb_table($tablename);
            $oldfield = new xmldb_field($oldfieldname);
            $newfield = new xmldb_field($newfieldname);

            if (
                $dbman->table_exists($table)
                && $dbman->field_exists($table, $oldfield)
                && !$dbman->field_exists($table, $newfield)
            ) {
                $dbman->rename_field($table, $oldfield, $newfieldname);
            }
        }

        // Plans may now rely on explicit entitlement rows without a Legacy access scope.
        // Drop the FK first because XMLDB refuses to alter a field with dependent indexes.
        $table = new xmldb_table('subscription_plan');
        $field = new xmldb_field(
            'accessscopeid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'name'
        );
        $accessscopekey = new xmldb_key(
            'access_scope_fk',
            XMLDB_KEY_FOREIGN,
            ['accessscopeid'],
            'subscription_access_scope',
            ['id']
        );

        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {
            $accessscopekeyname = $dbman->find_key_name($table, $accessscopekey);
            if ($accessscopekeyname !== false) {
                $dbman->drop_key($table, $accessscopekey);
            }

            $dbman->change_field_notnull($table, $field);

            if ($dbman->find_key_name($table, $accessscopekey) === false) {
                $dbman->add_key($table, $accessscopekey);
            }
        }

        // Remove the obsolete field that upgrade 2025061401 failed to drop.
        $table = new xmldb_table('subscription_access_scope');
        $field = new xmldb_field('description');
        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // A payment request can exist before the resulting subscription record.
        $table = new xmldb_table('subscription_payment_request');
        $field = new xmldb_field(
            'subscriptionid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null
        );
        $subscriptionkey = new xmldb_key(
            'subscriptionid',
            XMLDB_KEY_FOREIGN,
            ['subscriptionid'],
            'user_subscription',
            ['id']
        );
        if ($dbman->table_exists($table) && $dbman->field_exists($table, $field)) {
            $subscriptionkeyname = $dbman->find_key_name($table, $subscriptionkey);
            if ($subscriptionkeyname !== false) {
                $dbman->drop_key($table, $subscriptionkey);
            }

            $dbman->change_field_notnull($table, $field);

            if ($dbman->find_key_name($table, $subscriptionkey) === false) {
                $dbman->add_key($table, $subscriptionkey);
            }
        }

        // Fresh installs and upgraded sites must expose the same event lookup indexes.
        $table = new xmldb_table('subscription_event');
        if ($dbman->table_exists($table)) {
            $indexes = [
                new xmldb_index('subscriptionid_idx', XMLDB_INDEX_NOTUNIQUE, ['subscriptionid']),
                new xmldb_index('eventtype_idx', XMLDB_INDEX_NOTUNIQUE, ['eventtype']),
                new xmldb_index('provider_event_id_idx', XMLDB_INDEX_NOTUNIQUE, ['provider_event_id']),
            ];

            foreach ($indexes as $index) {
                if (!$dbman->index_exists($table, $index)) {
                    $dbman->add_index($table, $index);
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026072526, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072601) {
        $table = new xmldb_table('local_subs_commerce_grant');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('grantreference', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $table->add_field('idempotencykey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('purchasereference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('itemreference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('productsku', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('type', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
            $table->add_field('resourcekey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('quantity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('beneficiaryuserid', XMLDB_TYPE_INTEGER, '10');
            $table->add_field('beneficiaryemail', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL);
            $table->add_field('validfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('validuntil', XMLDB_TYPE_INTEGER, '10');
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'planned');
            $table->add_field('configurationjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

            $table->add_index('grantreference_uix', XMLDB_INDEX_UNIQUE, ['grantreference']);
            $table->add_index('idempotencykey_uix', XMLDB_INDEX_UNIQUE, ['idempotencykey']);
            $table->add_index('purchase_status_idx', XMLDB_INDEX_NOTUNIQUE, ['purchasereference', 'status']);
            $table->add_index('beneficiary_status_idx', XMLDB_INDEX_NOTUNIQUE, ['beneficiaryuserid', 'status']);
            $table->add_index('type_status_idx', XMLDB_INDEX_NOTUNIQUE, ['type', 'status']);
            $table->add_index('status_validuntil_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'validuntil']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026072601, 'local', 'subscriptions');
    }

    if ($oldversion < 2026072602) {
        $tables = [];

        $table = new xmldb_table('local_subs_commerce_dig_access');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('grantreference', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('idempotencykey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('purchasereference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('productsku', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('resourcekey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('beneficiaryuserid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('beneficiaryemail', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL);
        $table->add_field('downloadtoken', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('maxdownloads', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('downloadcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('validfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('validuntil', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'active');
        $table->add_field('lastdownloadat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('grantreference_uix', XMLDB_INDEX_UNIQUE, ['grantreference']);
        $table->add_index('idempotencykey_uix', XMLDB_INDEX_UNIQUE, ['idempotencykey']);
        $table->add_index('downloadtoken_uix', XMLDB_INDEX_UNIQUE, ['downloadtoken']);
        $table->add_index('beneficiary_status_idx', XMLDB_INDEX_NOTUNIQUE, ['beneficiaryuserid', 'status']);
        $table->add_index('email_status_idx', XMLDB_INDEX_NOTUNIQUE, ['beneficiaryemail', 'status']);
        $tables[] = $table;

        $table = new xmldb_table('local_subs_commerce_ful_state');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('grantreference', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('idempotencykey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('granttype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('handlerclass', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastexecutionreference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('lastsource', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('lastactoruserid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('lastpayloadjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('lastmessage', XMLDB_TYPE_TEXT);
        $table->add_field('lasterrorclass', XMLDB_TYPE_CHAR, '255');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('grantreference_uix', XMLDB_INDEX_UNIQUE, ['grantreference']);
        $table->add_index('idempotencykey_uix', XMLDB_INDEX_UNIQUE, ['idempotencykey']);
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('granttype_status_idx', XMLDB_INDEX_NOTUNIQUE, ['granttype', 'status']);
        $tables[] = $table;

        $table = new xmldb_table('local_subs_commerce_ful_attempt');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('grantreference', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('idempotencykey', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('executionreference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('granttype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('handlerclass', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('dryrun', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('source', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('actoruserid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('payloadjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('message', XMLDB_TYPE_TEXT);
        $table->add_field('errorclass', XMLDB_TYPE_CHAR, '255');
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('executionreference_uix', XMLDB_INDEX_UNIQUE, ['executionreference']);
        $table->add_index('grantreference_idx', XMLDB_INDEX_NOTUNIQUE, ['grantreference']);
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('granttype_status_idx', XMLDB_INDEX_NOTUNIQUE, ['granttype', 'status']);
        $tables[] = $table;

        foreach ($tables as $table) {
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        upgrade_plugin_savepoint(true, 2026072602, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072603) {
        $table = new xmldb_table('local_subs_commerce_shadow');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('executionreference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('purchasereference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('source', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('entrypoint', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('comparisonstatus', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $table->add_field('classification', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $table->add_field('legacyjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('nativejson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('differencesjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('errorclass', XMLDB_TYPE_CHAR, '255');
        $table->add_field('errormessage', XMLDB_TYPE_TEXT);
        $table->add_field('timestarted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timefinished', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('executionreference_uix', XMLDB_INDEX_UNIQUE, ['executionreference']);
        $table->add_index('purchase_time_idx', XMLDB_INDEX_NOTUNIQUE, ['purchasereference', 'timecreated']);
        $table->add_index('status_time_idx', XMLDB_INDEX_NOTUNIQUE, ['comparisonstatus', 'timecreated']);
        $table->add_index('classification_time_idx', XMLDB_INDEX_NOTUNIQUE, ['classification', 'timecreated']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026072603, 'local', 'subscriptions');
    }


    if ($oldversion < 2026072901) {
        $table = new xmldb_table('local_subs_commerce_promo');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('name', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL);
        $table->add_field('code', XMLDB_TYPE_CHAR, '100');
        $table->add_field('discounttype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('discountvalue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('currency', XMLDB_TYPE_CHAR, '3');
        $table->add_field('minimumcartminor', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('startsat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('endsat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('automatic', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('stackable', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('priority', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('globalusagelimit', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('userusagelimit', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('productskusjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('producttypesjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('code_uix', XMLDB_INDEX_UNIQUE, ['code']);
        $table->add_index('automatic_active_idx', XMLDB_INDEX_NOTUNIQUE, ['automatic', 'active', 'priority']);
        $table->add_index('active_window_idx', XMLDB_INDEX_NOTUNIQUE, ['active', 'startsat', 'endsat']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_subs_commerce_promouse');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('promotionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('cartreference', XMLDB_TYPE_CHAR, '100');
        $table->add_field('purchasereference', XMLDB_TYPE_CHAR, '100');
        $table->add_field('code', XMLDB_TYPE_CHAR, '100');
        $table->add_field('discountminor', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('currency', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'applied');
        $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('promotion_fk', XMLDB_KEY_FOREIGN, ['promotionid'], 'local_subs_commerce_promo', ['id']);
        $table->add_index('promotion_status_idx', XMLDB_INDEX_NOTUNIQUE, ['promotionid', 'status']);
        $table->add_index('promotion_user_status_idx', XMLDB_INDEX_NOTUNIQUE, ['promotionid', 'userid', 'status']);
        $table->add_index('purchase_idx', XMLDB_INDEX_NOTUNIQUE, ['purchasereference']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026072901, 'local', 'subscriptions');
    }

    if ($oldversion < 2026073001) {
        $table = new xmldb_table('local_subscriptions_commerce_payment');

        $fields = [
            new xmldb_field('providerorderid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'providerreference'),
            new xmldb_field('paymenturl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'metadatajson'),
            new xmldb_field('providerpayload', XMLDB_TYPE_TEXT, null, null, null, null, null, 'paymenturl'),
            new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'providerpayload'),
            new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timecreated'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $providerorderindex = new xmldb_index(
            'provider_order_idx',
            XMLDB_INDEX_NOTUNIQUE,
            ['provider', 'providerorderid']
        );
        if (!$dbman->index_exists($table, $providerorderindex)) {
            $dbman->add_index($table, $providerorderindex);
        }

        $purchasestatusindex = new xmldb_index(
            'purchase_status_idx',
            XMLDB_INDEX_NOTUNIQUE,
            ['purchaseid', 'status']
        );
        if (!$dbman->index_exists($table, $purchasestatusindex)) {
            $dbman->add_index($table, $purchasestatusindex);
        }

        $now = time();
        $DB->execute(
            "UPDATE {local_subscriptions_commerce_payment}
                SET timecreated = :timecreated
              WHERE timecreated = 0",
            ['timecreated' => $now]
        );
        $DB->execute(
            "UPDATE {local_subscriptions_commerce_payment}
                SET timemodified = timecreated
              WHERE timemodified = 0"
        );

        upgrade_plugin_savepoint(true, 2026073001, 'local', 'subscriptions');
    }


    if ($oldversion < 2026073002) {
        $table = new xmldb_table('local_subs_commerce_guest');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('reference', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('token', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'identity_pending');
        $table->add_field('currency', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('email', XMLDB_TYPE_CHAR, '254');
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '100');
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '100');
        $table->add_field('purchasereference', XMLDB_TYPE_CHAR, '100');
        $table->add_field('paymentreference', XMLDB_TYPE_CHAR, '100');
        $table->add_field('expiresat', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('reference_uix', XMLDB_INDEX_UNIQUE, ['reference']);
        $table->add_index('token_uix', XMLDB_INDEX_UNIQUE, ['token']);
        $table->add_index('status_expiry_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'expiresat']);
        $table->add_index('email_status_idx', XMLDB_INDEX_NOTUNIQUE, ['email', 'status']);
        $table->add_index('user_status_idx', XMLDB_INDEX_NOTUNIQUE, ['userid', 'status']);
        $table->add_index('purchase_idx', XMLDB_INDEX_NOTUNIQUE, ['purchasereference']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026073002, 'local', 'subscriptions');
    }


    if ($oldversion < 2026073101) {
        $table = new xmldb_table('local_subs_commerce_mail');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('mailtype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'queued');
        $table->add_field('idempotencykey', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL);
        $table->add_field('purchaseid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('recipientemail', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL);
        $table->add_field('recipientname', XMLDB_TYPE_CHAR, '255');
        $table->add_field('language', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('subject', XMLDB_TYPE_TEXT);
        $table->add_field('contextjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('attemptcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('maxattempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '5');
        $table->add_field('nextruntime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lasterror', XMLDB_TYPE_TEXT);
        $table->add_field('timeprocessing', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timesent', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('idempotency_uix', XMLDB_INDEX_UNIQUE, ['idempotencykey']);
        $table->add_index('status_runtime_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'nextruntime']);
        $table->add_index('purchase_idx', XMLDB_INDEX_NOTUNIQUE, ['purchaseid']);
        $table->add_index('recipient_idx', XMLDB_INDEX_NOTUNIQUE, ['recipientemail']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026073101, 'local', 'subscriptions');
    }


    if ($oldversion < 2026073102) {
        $table = new xmldb_table('local_subs_commerce_mail_tpl');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('mailtype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('language', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('preheader', XMLDB_TYPE_TEXT);
        $table->add_field('heading', XMLDB_TYPE_CHAR, '255');
        $table->add_field('introhtml', XMLDB_TYPE_TEXT);
        $table->add_field('introformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('outrohtml', XMLDB_TYPE_TEXT);
        $table->add_field('outroformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('signaturehtml', XMLDB_TYPE_TEXT);
        $table->add_field('signatureformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('headerimage', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('type_lang_uix', XMLDB_INDEX_UNIQUE, ['mailtype', 'language']);
        $table->add_index('enabled_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
        $table->add_index('modified_idx', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026073102, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080500) {
        $table = new xmldb_table('local_subs_showroom');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('showroomkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'draft');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('template', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('slugfr', XMLDB_TYPE_CHAR, '191');
        $table->add_field('slugen', XMLDB_TYPE_CHAR, '191');
        $table->add_field('slugru', XMLDB_TYPE_CHAR, '191');
        $table->add_field('titlekey', XMLDB_TYPE_CHAR, '191');
        $table->add_field('descriptionkey', XMLDB_TYPE_CHAR, '191');
        $table->add_field('productsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('settingsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('showroomkey_uix', XMLDB_INDEX_UNIQUE, ['showroomkey']);
        $table->add_index('status_idx', XMLDB_INDEX_NOTUNIQUE, ['status']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $blocktable = new xmldb_table('local_subs_showroom_block');
        $blocktable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $blocktable->add_field('showroomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $blocktable->add_field('blockkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $blocktable->add_field('blocktype', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $blocktable->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $blocktable->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $blocktable->add_field('configjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $blocktable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $blocktable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $blocktable->add_field('usermodified', XMLDB_TYPE_INTEGER, '10');
        $blocktable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $blocktable->add_key('showroom_fk', XMLDB_KEY_FOREIGN, ['showroomid'], 'local_subs_showroom', ['id']);
        $blocktable->add_index('showroom_block_uix', XMLDB_INDEX_UNIQUE, ['showroomid', 'blockkey']);
        $blocktable->add_index('showroom_sort_idx', XMLDB_INDEX_NOTUNIQUE, ['showroomid', 'sortorder']);
        if (!$dbman->table_exists($blocktable)) {
            $dbman->create_table($blocktable);
        }

        if (!$DB->record_exists('local_subs_showroom', ['showroomkey' => 'third-group-verbs'])) {
            $now = time();
            $showroomid = $DB->insert_record('local_subs_showroom', (object)[
                'showroomkey' => 'third-group-verbs',
                'status' => 'draft',
                'name' => 'Verbes du 3e groupe',
                'template' => 'local_subscriptions/showroom/third_group_verbs',
                'slugfr' => 'verbes-3e-groupe',
                'slugen' => 'third-group-verbs',
                'slugru' => 'glagoly-tretey-gruppy',
                'titlekey' => 'commerce_showroom_third_group_verbs_title',
                'descriptionkey' => 'commerce_showroom_third_group_verbs_description',
                'productsjson' => json_encode([
                    'course' => 'SUB.PLAN.30',
                    'pdf' => 'DIGITAL.VERBES-3E-GROUPE',
                    'bundle' => 'BUNDLEA1VERBES',
                ], JSON_UNESCAPED_SLASHES),
                'settingsjson' => '{}',
                'timecreated' => $now,
                'timemodified' => $now,
                'usermodified' => null,
            ]);
            $types = ['hero', 'stats', 'video', 'journey', 'exercise_explorer', 'offers', 'comparison', 'method', 'faq', 'support', 'cta'];
            foreach ($types as $sortorder => $type) {
                $DB->insert_record('local_subs_showroom_block', (object)[
                    'showroomid' => $showroomid,
                    'blockkey' => $type,
                    'blocktype' => $type,
                    'sortorder' => ($sortorder + 1) * 10,
                    'enabled' => 1,
                    'configjson' => '{}',
                    'timecreated' => $now,
                    'timemodified' => $now,
                    'usermodified' => null,
                ]);
            }
        }

        upgrade_plugin_savepoint(true, 2026080500, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080504) {
        $table = new xmldb_table('local_subs_showroom_rev');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('showroomid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('revisionno', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('action', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $table->add_field('note', XMLDB_TYPE_TEXT);
        $table->add_field('snapshotjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('showroom_fk', XMLDB_KEY_FOREIGN, ['showroomid'], 'local_subs_showroom', ['id']);
        $table->add_index('showroom_revision_uix', XMLDB_INDEX_UNIQUE, ['showroomid', 'revisionno']);
        $table->add_index('showroom_created_idx', XMLDB_INDEX_NOTUNIQUE, ['showroomid', 'timecreated']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026080504, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080701) {
        $table = new xmldb_table('local_subs_commerce_offer');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('offeruuid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $table->add_field('campaignkey', XMLDB_TYPE_CHAR, '100');
        $table->add_field('sourcepurchaseid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('targetproductid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('beneficiaryuserid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('beneficiaryemail', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'issued');
        $table->add_field('validfrom', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('expiresat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('termsversion', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('termsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('metadatajson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('redeemedat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('redeemedpurchaseid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('revokedat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('revokedbyuserid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('revokereason', XMLDB_TYPE_TEXT);
        $table->add_field('issuedbyuserid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('sourcepurchase_fk', XMLDB_KEY_FOREIGN, ['sourcepurchaseid'], 'local_subscriptions_commerce_purchase', ['id']);
        $table->add_key('targetproduct_fk', XMLDB_KEY_FOREIGN, ['targetproductid'], 'local_subs_commerce_product', ['id']);
        $table->add_key('redeemedpurchase_fk', XMLDB_KEY_FOREIGN, ['redeemedpurchaseid'], 'local_subscriptions_commerce_purchase', ['id']);
        $table->add_index('offeruuid_uix', XMLDB_INDEX_UNIQUE, ['offeruuid']);
        $table->add_index('campaign_status_idx', XMLDB_INDEX_NOTUNIQUE, ['campaignkey', 'status']);
        $table->add_index('beneficiary_user_status_idx', XMLDB_INDEX_NOTUNIQUE, ['beneficiaryuserid', 'status']);
        $table->add_index('beneficiary_email_status_idx', XMLDB_INDEX_NOTUNIQUE, ['beneficiaryemail', 'status']);
        $table->add_index('target_product_status_idx', XMLDB_INDEX_NOTUNIQUE, ['targetproductid', 'status']);
        $table->add_index('expires_status_idx', XMLDB_INDEX_NOTUNIQUE, ['expiresat', 'status']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026080701, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080702) {
        $table = new xmldb_table('local_subs_commerce_offer_token');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('offerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('tokenversion', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('tokenhash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('issuancekey', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL);
        $table->add_field('requesthash', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('offer_fk', XMLDB_KEY_FOREIGN, ['offerid'], 'local_subs_commerce_offer', ['id']);
        $table->add_index('tokenhash_uix', XMLDB_INDEX_UNIQUE, ['tokenhash']);
        $table->add_index('issuancekey_uix', XMLDB_INDEX_UNIQUE, ['issuancekey']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2026080702, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080800) {
        $table = new xmldb_table('local_subs_commerce_offer_campaign');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('campaignkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('audiencetype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('sourceproductsku', XMLDB_TYPE_CHAR, '100');
        $table->add_field('targetproductid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('termsversion', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('termsjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('criteriajson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('validfrom', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('expiresat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'draft');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('targetproduct_fk', XMLDB_KEY_FOREIGN, ['targetproductid'], 'local_subs_commerce_product', ['id']);
        $table->add_index('campaignkey_uix', XMLDB_INDEX_UNIQUE, ['campaignkey']);
        $table->add_index('status_modified_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'timemodified']);
        if (!$dbman->table_exists($table)) { $dbman->create_table($table); }

        $member = new xmldb_table('local_subs_commerce_offer_campaign_member');
        $member->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $member->add_field('campaignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $member->add_field('memberkey', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL);
        $member->add_field('purchaseid', XMLDB_TYPE_INTEGER, '10');
        $member->add_field('userid', XMLDB_TYPE_INTEGER, '10');
        $member->add_field('email', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL);
        $member->add_field('eligibilitystatus', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $member->add_field('reason', XMLDB_TYPE_TEXT);
        $member->add_field('offerid', XMLDB_TYPE_INTEGER, '10');
        $member->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $member->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $member->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $member->add_key('campaign_fk', XMLDB_KEY_FOREIGN, ['campaignid'], 'local_subs_commerce_offer_campaign', ['id']);
        $member->add_key('purchase_fk', XMLDB_KEY_FOREIGN, ['purchaseid'], 'local_subscriptions_commerce_purchase', ['id']);
        $member->add_key('offer_fk', XMLDB_KEY_FOREIGN, ['offerid'], 'local_subs_commerce_offer', ['id']);
        $member->add_index('campaign_member_uix', XMLDB_INDEX_UNIQUE, ['campaignid', 'memberkey']);
        $member->add_index('campaign_status_idx', XMLDB_INDEX_NOTUNIQUE, ['campaignid', 'eligibilitystatus']);
        $member->add_index('email_idx', XMLDB_INDEX_NOTUNIQUE, ['email']);
        if (!$dbman->table_exists($member)) { $dbman->create_table($member); }

        upgrade_plugin_savepoint(true, 2026080800, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080801) {
        // K9 registers the dedicated throttled Personal Offer mail scheduled task.
        upgrade_plugin_savepoint(true, 2026080801, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080901) {
        $table = new xmldb_table('local_subs_commerce_grant_campaign');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('campaignkey', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('sourcetype', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $table->add_field('sourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('sourcejson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('targetproductid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('targetjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $table->add_field('reason', XMLDB_TYPE_TEXT);
        $table->add_field('status', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'ready');
        $table->add_field('selectedcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('processedcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('successcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('skippedcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('failedcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('startedat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('completedat', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('targetproduct_fk', XMLDB_KEY_FOREIGN, ['targetproductid'], 'local_subs_commerce_product', ['id']);
        $table->add_index('campaignkey_uix', XMLDB_INDEX_UNIQUE, ['campaignkey']);
        $table->add_index('status_created_idx', XMLDB_INDEX_NOTUNIQUE, ['status', 'timecreated']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $member = new xmldb_table('local_subs_commerce_grant_campaign_member');
        $member->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $member->add_field('campaignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $member->add_field('memberkey', XMLDB_TYPE_CHAR, '191', null, XMLDB_NOTNULL);
        $member->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $member->add_field('firstname', XMLDB_TYPE_CHAR, '100');
        $member->add_field('lastname', XMLDB_TYPE_CHAR, '100');
        $member->add_field('email', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL);
        $member->add_field('evidencejson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $member->add_field('ownershipsource', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'none');
        $member->add_field('plannedgrantcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $member->add_field('status', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, 'queued');
        $member->add_field('attempts', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $member->add_field('lasterror', XMLDB_TYPE_TEXT);
        $member->add_field('lastattemptat', XMLDB_TYPE_INTEGER, '10');
        $member->add_field('completedat', XMLDB_TYPE_INTEGER, '10');
        $member->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $member->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $member->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $member->add_key('campaign_fk', XMLDB_KEY_FOREIGN, ['campaignid'], 'local_subs_commerce_grant_campaign', ['id']);
        $member->add_key('user_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $member->add_index('campaign_member_uix', XMLDB_INDEX_UNIQUE, ['campaignid', 'memberkey']);
        $member->add_index('campaign_status_idx', XMLDB_INDEX_NOTUNIQUE, ['campaignid', 'status']);
        if (!$dbman->table_exists($member)) {
            $dbman->create_table($member);
        }

        upgrade_plugin_savepoint(true, 2026080901, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080902) {
        $table = new xmldb_table('local_subs_commerce_grant_campaign');
        $field = new xmldb_field(
            'sendemail',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '1',
            'reason'
        );
        if ($dbman->table_exists($table) && !$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026080902, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080903) {
        $table = new xmldb_table('local_subs_commerce_offer_campaign_member');

        $fields = [
            new xmldb_field('firstname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'userid'),
            new xmldb_field('lastname', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'firstname'),
            new xmldb_field('evidencejson', XMLDB_TYPE_TEXT, null, null, null, null, null, 'email'),
            new xmldb_field('existingofferid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'reason'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $key = new xmldb_key(
            'existingoffer_fk',
            XMLDB_KEY_FOREIGN,
            ['existingofferid'],
            'local_subs_commerce_offer',
            ['id']
        );
        if (!$dbman->find_key_name($table, $key)) {
            $dbman->add_key($table, $key);
        }

        upgrade_plugin_savepoint(true, 2026080903, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080904) {
        $table = new xmldb_table('local_subs_commerce_offer_campaign');

        $fields = [
            new xmldb_field('snapshotat', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'expiresat'),
            new xmldb_field('snapshothash', XMLDB_TYPE_CHAR, '64', null, null, null, null, 'snapshotat'),
            new xmldb_field('selectedcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'snapshothash'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $snapshotselected = new xmldb_field(
            'snapshotselected',
            XMLDB_TYPE_INTEGER,
            '1',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'existingofferid'
        );
        $membertable = new xmldb_table('local_subs_commerce_offer_campaign_member');
        if (!$dbman->field_exists($membertable, $snapshotselected)) {
            $dbman->add_field($membertable, $snapshotselected);
        }

        // Fresh installs already get this FK from install.xml. Existing K15A/B
        // installations may have the field without the key.
        $existingofferkey = new xmldb_key(
            'existingoffer_fk',
            XMLDB_KEY_FOREIGN,
            ['existingofferid'],
            'local_subs_commerce_offer',
            ['id']
        );
        if (
            $dbman->table_exists($membertable)
            && $dbman->field_exists($membertable, new xmldb_field('existingofferid'))
            && !$dbman->find_key_name($membertable, $existingofferkey)
        ) {
            $dbman->add_key($membertable, $existingofferkey);
        }

        upgrade_plugin_savepoint(true, 2026080904, 'local', 'subscriptions');
    }


    if ($oldversion < 2026080905) {
        $table = new xmldb_table('local_subs_commerce_offer_campaign');

        $fields = [
            new xmldb_field(
                'certifiedat',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null,
                'selectedcount'
            ),
            new xmldb_field(
                'certifiedby',
                XMLDB_TYPE_INTEGER,
                '10',
                null,
                null,
                null,
                null,
                'certifiedat'
            ),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2026080905, 'local', 'subscriptions');
    }


    if ($oldversion < 2026081101) {
        // M3A: per-campaign Personal Offer email configuration and translations.
        $config = new xmldb_table('local_subs_commerce_offer_campaign_email_config');
        $config->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $config->add_field('campaignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $config->add_field('ctadestination', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'checkout');
        $config->add_field('showroomid', XMLDB_TYPE_INTEGER, '10');
        $config->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $config->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $config->add_field('usercreated', XMLDB_TYPE_INTEGER, '10');
        $config->add_field('usermodified', XMLDB_TYPE_INTEGER, '10');
        $config->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $config->add_key(
            'campaign_fk',
            XMLDB_KEY_FOREIGN_UNIQUE,
            ['campaignid'],
            'local_subs_commerce_offer_campaign',
            ['id']
        );
        $config->add_key('showroom_fk', XMLDB_KEY_FOREIGN, ['showroomid'], 'local_subs_showroom', ['id']);
        if (!$dbman->table_exists($config)) {
            $dbman->create_table($config);
        }

        $content = new xmldb_table('local_subs_commerce_offer_campaign_email_content');
        $content->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $content->add_field('campaignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $content->add_field('language', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL);
        $content->add_field('subject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $content->add_field('body', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $content->add_field('bodyformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $content->add_field('ctalabel', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $content->add_field('closing', XMLDB_TYPE_TEXT);
        $content->add_field('closingformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $content->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $content->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $content->add_field('usercreated', XMLDB_TYPE_INTEGER, '10');
        $content->add_field('usermodified', XMLDB_TYPE_INTEGER, '10');
        $content->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $content->add_key(
            'campaign_fk',
            XMLDB_KEY_FOREIGN,
            ['campaignid'],
            'local_subs_commerce_offer_campaign',
            ['id']
        );
        $content->add_index('campaign_language_uix', XMLDB_INDEX_UNIQUE, ['campaignid', 'language']);
        if (!$dbman->table_exists($content)) {
            $dbman->create_table($content);
        }

        // No backfill by design: absence of M3A rows means the legacy Personal
        // Offer email renderer remains authoritative for existing campaigns.
        upgrade_plugin_savepoint(true, 2026081101, 'local', 'subscriptions');
    }


    if ($oldversion < 2026081102) {
        // M4.2D: auditable customer identity merge execution.
        $merge = new xmldb_table('local_subs_identity_merge');
        $merge->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $merge->add_field('mergeuuid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $merge->add_field('targetuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $merge->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'completed');
        $merge->add_field('planjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $merge->add_field('resultjson', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
        $merge->add_field('performedby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $merge->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $merge->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $merge->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $merge->add_index('mergeuuid_uix', XMLDB_INDEX_UNIQUE, ['mergeuuid']);
        $merge->add_index('target_time_idx', XMLDB_INDEX_NOTUNIQUE, ['targetuserid', 'timecreated']);
        $merge->add_index('actor_time_idx', XMLDB_INDEX_NOTUNIQUE, ['performedby', 'timecreated']);
        if (!$dbman->table_exists($merge)) {
            $dbman->create_table($merge);
        }

        $source = new xmldb_table('local_subs_identity_merge_source');
        $source->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $source->add_field('mergeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $source->add_field('sourceuserid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $source->add_field('sourceemail', XMLDB_TYPE_CHAR, '254');
        $source->add_field('wassuspended', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $source->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $source->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $source->add_key(
            'merge_fk',
            XMLDB_KEY_FOREIGN,
            ['mergeid'],
            'local_subs_identity_merge',
            ['id']
        );
        $source->add_index('merge_source_uix', XMLDB_INDEX_UNIQUE, ['mergeid', 'sourceuserid']);
        $source->add_index('source_user_idx', XMLDB_INDEX_NOTUNIQUE, ['sourceuserid']);
        if (!$dbman->table_exists($source)) {
            $dbman->create_table($source);
        }

        upgrade_plugin_savepoint(true, 2026081102, 'local', 'subscriptions');
    }


    if ($oldversion < 2026081103) {
        // M3G.1: fixed local date/time or duration from Personal Offer issuance.
        $table = new xmldb_table('local_subs_commerce_offer_campaign');
        $field = new xmldb_field('validitymode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'legacy', 'expiresat');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }
        $field = new xmldb_field('validityduration', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'validitymode');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }
        $field = new xmldb_field('validitytimezone', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, 'Europe/Paris', 'validityduration');
        if (!$dbman->field_exists($table, $field)) { $dbman->add_field($table, $field); }
        upgrade_plugin_savepoint(true, 2026081103, 'local', 'subscriptions');
    }


    if ($oldversion < 2026081202) {
        // M3 schema repair: fresh-install schema and upgraded databases must expose
        // the same campaign validity fields. Intentionally idempotent.
        $table = new xmldb_table('local_subs_commerce_offer_campaign');

        $field = new xmldb_field(
            'validitymode',
            XMLDB_TYPE_CHAR,
            '20',
            null,
            XMLDB_NOTNULL,
            null,
            'legacy',
            'expiresat'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'validityduration',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            null,
            null,
            null,
            'validitymode'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field(
            'validitytimezone',
            XMLDB_TYPE_CHAR,
            '64',
            null,
            XMLDB_NOTNULL,
            null,
            'Europe/Paris',
            'validityduration'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026081202, 'local', 'subscriptions');
    }

    return true;
}