<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateChannels extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'business_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'platform' => [
                'type'       => 'ENUM',
                'constraint' => ['whatsapp', 'messenger', 'instagram', 'telegram'],
                'null'       => false,
            ],
            // WA phone_number_id, page id, bot token id, etc.
            'external_account_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => false,
            ],
            // Encrypted platform tokens. Plain TEXT for now; encryption is out of scope.
            'credentials_encrypted' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        // Draft schema: UNIQUE KEY uniq_channel (platform, external_account_id).
        $this->forge->addUniqueKey(['platform', 'external_account_id'], 'uniq_channel');
        // Explicit index on the FK column; supports the inbox query's
        // channels(business_id) -> conversations(channel_id) join path.
        $this->forge->addKey('business_id', false, false, 'idx_channels_business');

        // CI4 addForeignKey signature: (field, table, tableField, onUpdate, onDelete, name).
        $this->forge->addForeignKey(
            'business_id',
            'businesses',
            'id',
            'CASCADE', // ON UPDATE
            'CASCADE', // ON DELETE
            'fk_channels_business'
        );

        $this->forge->createTable('channels', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('channels', 'fk_channels_business');
        $this->forge->dropTable('channels', true);
    }
}
