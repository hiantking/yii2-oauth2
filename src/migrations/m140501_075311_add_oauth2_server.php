<?php

use yii\db\Schema;

class m140501_075311_add_oauth2_server extends \yii\db\Migration
{

    public function mysql($yes,$no='') {
        return $this->db->driverName === 'mysql' ? $yes : $no;
    }

    public function setPrimaryKey($columns) {
        return 'PRIMARY KEY (' . $this->db->getQueryBuilder()->buildColumns($columns) . ')';
    }

    public function foreignKey($columns,$refTable,$refColumns,$onDelete = null,$onUpdate = null) {
        $builder = $this->db->getQueryBuilder();
        $sql = ' FOREIGN KEY (' . $builder->buildColumns($columns) . ')'
            . ' REFERENCES ' . $this->db->quoteTableName($refTable)
            . ' (' . $builder->buildColumns($refColumns) . ')';
        if ($onDelete !== null) {
            $sql .= ' ON DELETE ' . $onDelete;
        }
        if ($onUpdate !== null) {
            $sql .= ' ON UPDATE ' . $onUpdate;
        }
        return $sql;
    }

    public function up()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_general_ci ENGINE=InnoDB';
        }

        $now            = $this->mysql('CURRENT_TIMESTAMP',"'now'");
        $on_update_now  = $this->mysql("ON UPDATE $now");

        $transaction = $this->db->beginTransaction();
        try {
            $this->createTable('{{%oauth_clients}}', [
                'client_id' => Schema::TYPE_STRING . '(32) NOT NULL',
                'secret' => Schema::TYPE_STRING . '(40) DEFAULT NULL',
                'name' => Schema::TYPE_STRING . '(100) DEFAULT NULL',
                'redirect_uri' => Schema::TYPE_STRING . '(1000) NOT NULL',
                'grant_types' => Schema::TYPE_STRING . '(100) NOT NULL',
                'scopes' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                'user_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'is_public' => Schema::TYPE_BOOLEAN . ' DEFAULT NULL',
                $this->setPrimaryKey('client_id'),
            ], $tableOptions);

            $this->createTable('{{%oauth_access_tokens}}', [
                'access_token' => Schema::TYPE_STRING . '(40) NOT NULL',
                'client_id' => Schema::TYPE_STRING . '(32) NOT NULL',
                'user_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'expiry' => Schema::TYPE_TIMESTAMP . " NOT NULL DEFAULT $now $on_update_now",
                'scopes' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                $this->setPrimaryKey('access_token'),
                $this->foreignKey('client_id','{{%oauth_clients}}','client_id','CASCADE','CASCADE'),
            ], $tableOptions);

            $this->createTable('{{%oauth_refresh_tokens}}', [
                'refresh_token' => Schema::TYPE_STRING . '(40) NOT NULL',
                'client_id' => Schema::TYPE_STRING . '(32) NOT NULL',
                'user_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'expiry' => Schema::TYPE_TIMESTAMP . " NOT NULL DEFAULT $now $on_update_now",
                'scopes' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                $this->setPrimaryKey('refresh_token'),
                $this->foreignKey('client_id','{{%oauth_clients}}','client_id','CASCADE','CASCADE'),
            ], $tableOptions);

            $this->createTable('{{%oauth_authorization_codes}}', [
                'authorization_code' => Schema::TYPE_STRING . '(40) NOT NULL',
                'client_id' => Schema::TYPE_STRING . '(32) NOT NULL',
                'user_id' => Schema::TYPE_INTEGER . ' DEFAULT NULL',
                'redirect_uri' => Schema::TYPE_STRING . '(1000) NOT NULL',
                'expiry' => Schema::TYPE_TIMESTAMP . " NOT NULL DEFAULT $now $on_update_now",
                'scopes' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                $this->setPrimaryKey('authorization_code'),
                $this->foreignKey('client_id','{{%oauth_clients}}','client_id','CASCADE','CASCADE'),
            ], $tableOptions);

            $this->createTable('{{%oauth_scopes}}', [
                'client_id' => Schema::TYPE_STRING . '(32) NOT NULL',
                'scopes' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                'is_default' => Schema::TYPE_BOOLEAN . ' NOT NULL',
                'definition' => Schema::TYPE_STRING . '(100) NOT NULL',
            ], $tableOptions);

            $this->createTable('{{%oauth_jwt}}', [
                'client_id' => Schema::TYPE_STRING . '(32) NOT NULL',
                'subject' => Schema::TYPE_STRING . '(80) DEFAULT NULL',
                'public_key' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                $this->setPrimaryKey('client_id'),
            ], $tableOptions);

            $this->createTable('{{%users}}', [
                'username' => Schema::TYPE_STRING . '(255) NOT NULL',
                'password' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                'first_name' => Schema::TYPE_STRING . '(255) DEFAULT NULL',
                'last_name' => Schema::TYPE_STRING . '(255) DEFAULT NULL',
                $this->setPrimaryKey('username'),
            ], $tableOptions);

            $this->createTable('{{%oauth_cypher_keys}}', [
                'client_id' => Schema::TYPE_STRING . '(255) NOT NULL',
                'public_key' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                'private_key' => Schema::TYPE_STRING . '(2000) DEFAULT NULL',
                'encryption_algorithm' => Schema::TYPE_STRING . '(100) DEFAULT \'RS256\'',
            ], $tableOptions);

            // insert client data
            $this->batchInsert('{{%oauth_clients}}', ['client_id', 'secret', 'redirect_uri', 'grant_types'], [
                ['testclient', 'testpass', 'http://fake/', 'client_credentials authorization_code password implicit'],
            ]);

            $transaction->commit();
        } catch (Exception $e) {
            echo 'Exception: ' . $e->getMessage() . '\n';
            $transaction->rollback();

            return false;
        }

        return true;
    }

    public function down()
    {
        $transaction = $this->db->beginTransaction();
        try {
            $this->dropTable('{{%users}}');
            $this->dropTable('{{%oauth_jwt}}');
            $this->dropTable('{{%oauth_scopes}}');
            $this->dropTable('{{%oauth_authorization_codes}}');
            $this->dropTable('{{%oauth_refresh_tokens}}');
            $this->dropTable('{{%oauth_access_tokens}}');
            $this->dropTable('{{%oauth_cypher_keys}}');
            $this->dropTable('{{%oauth_clients}}');

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollback();
            echo $e->getMessage();
            echo "\n";
            echo get_called_class() . ' cannot be reverted.';
            echo "\n";

            return false;
        }

        return true;
    }
}
