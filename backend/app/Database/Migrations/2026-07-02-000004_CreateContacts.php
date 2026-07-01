<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContacts extends Migration
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
            'channel_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            // WA wa_id, PSID, IG-scoped id, Telegram chat_id.
            'external_contact_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => false,
            ],
            'display_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        // Draft schema: UNIQUE KEY uniq_contact (channel_id, external_contact_id).
        // This composite also serves as the index for the FK column channel_id
        // (channel_id is the leftmost prefix), so no separate FK index is needed.
        $this->forge->addUniqueKey(['channel_id', 'external_contact_id'], 'uniq_contact');

        // CI4 addForeignKey signature: (field, table, tableField, onUpdate, onDelete, name).
        $this->forge->addForeignKey(
            'channel_id',
            'channels',
            'id',
            'CASCADE', // ON UPDATE
            'CASCADE', // ON DELETE
            'fk_contacts_channel'
        );

        $this->forge->createTable('contacts', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('contacts', 'fk_contacts_channel');
        $this->forge->dropTable('contacts', true);
    }
}
