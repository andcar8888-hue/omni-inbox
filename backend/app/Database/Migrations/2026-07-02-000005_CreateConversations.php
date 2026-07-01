<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateConversations extends Migration
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
            'contact_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'assigned_user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['open', 'pending', 'closed'],
                'default'    => 'open',
                'null'       => false,
            ],
            'last_message_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'unread_count' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'default'    => 0,
                'null'       => false,
            ],
        ]);

        $this->forge->addKey('id', true);

        // Main inbox query: conversations for a business ordered by last_message_at desc.
        // The business filter reaches conversations via channels(business_id) ->
        // conversations(channel_id). This composite lets MySQL filter by channel_id
        // and satisfy the ORDER BY last_message_at from the same index (no filesort)
        // for a single channel, and greatly narrows the scan when channels are IN(...).
        $this->forge->addKey(['channel_id', 'last_message_at'], false, false, 'idx_conv_channel_last_message');

        // Enforce the domain rule: a conversation is unique per (channel, contact).
        // The draft SQL did not declare this UNIQUE, but CLAUDE.md states a
        // conversation is unique per (business_channel_id, external_contact_id).
        // contact_id already encodes (channel_id, external_contact_id) via the
        // contacts unique key, so (channel_id, contact_id) is the correct guard.
        $this->forge->addUniqueKey(['channel_id', 'contact_id'], 'uniq_conv_channel_contact');

        // Supports "conversations assigned to this agent" lookups.
        $this->forge->addKey('assigned_user_id', false, false, 'idx_conv_assigned_user');

        // FK index for contact_id (not covered as a leftmost prefix elsewhere).
        $this->forge->addKey('contact_id', false, false, 'idx_conv_contact');

        // CI4 addForeignKey signature: (field, table, tableField, onUpdate, onDelete, name).
        $this->forge->addForeignKey(
            'channel_id',
            'channels',
            'id',
            'CASCADE', // ON UPDATE
            'CASCADE', // ON DELETE
            'fk_conv_channel'
        );
        $this->forge->addForeignKey(
            'contact_id',
            'contacts',
            'id',
            'CASCADE', // ON UPDATE
            'CASCADE', // ON DELETE
            'fk_conv_contact'
        );
        // assigned_user_id is nullable; unassign (SET NULL) rather than delete
        // the conversation when the agent's user row is removed.
        $this->forge->addForeignKey(
            'assigned_user_id',
            'users',
            'id',
            'CASCADE',  // ON UPDATE
            'SET NULL', // ON DELETE: unassign, keep the conversation.
            'fk_conv_assigned_user'
        );

        $this->forge->createTable('conversations', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('conversations', 'fk_conv_channel');
        $this->forge->dropForeignKey('conversations', 'fk_conv_contact');
        $this->forge->dropForeignKey('conversations', 'fk_conv_assigned_user');
        $this->forge->dropTable('conversations', true);
    }
}
