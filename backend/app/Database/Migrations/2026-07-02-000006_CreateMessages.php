<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMessages extends Migration
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
            'conversation_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => false,
            ],
            'direction' => [
                'type'       => 'ENUM',
                'constraint' => ['inbound', 'outbound'],
                'null'       => false,
            ],
            // Set for outbound messages.
            'sender_user_id' => [
                'type'       => 'BIGINT',
                'constraint' => 20,
                'unsigned'   => true,
                'null'       => true,
            ],
            // Platform's own message id, for dedupe.
            'external_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => true,
            ],
            'body' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'attachments_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['sent', 'delivered', 'read', 'failed'],
                'default'    => 'sent',
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);

        // Draft schema: UNIQUE KEY uniq_external_message (external_message_id).
        // Enforces webhook idempotency (dedupe on the platform's own id).
        // NOTE: external_message_id is nullable and MySQL allows multiple NULLs
        // under a UNIQUE key, so outbound rows that have not yet received a
        // platform id are not blocked. This matches the draft intent.
        $this->forge->addUniqueKey('external_message_id', 'uniq_external_message');

        // Thread view: fetch a conversation's messages in chronological order.
        $this->forge->addKey(['conversation_id', 'created_at'], false, false, 'idx_msg_conversation_created');

        // FK index for sender_user_id.
        $this->forge->addKey('sender_user_id', false, false, 'idx_msg_sender_user');

        // CI4 addForeignKey signature: (field, table, tableField, onUpdate, onDelete, name).
        $this->forge->addForeignKey(
            'conversation_id',
            'conversations',
            'id',
            'CASCADE', // ON UPDATE
            'CASCADE', // ON DELETE
            'fk_msg_conversation'
        );
        // sender_user_id is nullable; keep the message but null the sender if the
        // agent's user row is removed (preserve conversation history).
        $this->forge->addForeignKey(
            'sender_user_id',
            'users',
            'id',
            'CASCADE',  // ON UPDATE
            'SET NULL', // ON DELETE: keep the message, null the sender.
            'fk_msg_sender_user'
        );

        $this->forge->createTable('messages', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('messages', 'fk_msg_conversation');
        $this->forge->dropForeignKey('messages', 'fk_msg_sender_user');
        $this->forge->dropTable('messages', true);
    }
}
