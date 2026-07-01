<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => false,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => false,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['owner', 'agent'],
                'default'    => 'agent',
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        // UNIQUE email per draft schema.
        $this->forge->addUniqueKey('email', 'uniq_users_email');
        // Explicit index on the FK column to support "list users for a business".
        $this->forge->addKey('business_id', false, false, 'idx_users_business');

        // CI4 addForeignKey signature: (field, table, tableField, onUpdate, onDelete, name).
        $this->forge->addForeignKey(
            'business_id',
            'businesses',
            'id',
            'CASCADE', // ON UPDATE
            'CASCADE', // ON DELETE: removing a business removes its users.
            'fk_users_business'
        );

        $this->forge->createTable('users', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
        ]);
    }

    public function down(): void
    {
        // Drop FK first so the table drop cannot fail on a dependency lock.
        $this->forge->dropForeignKey('users', 'fk_users_business');
        $this->forge->dropTable('users', true);
    }
}
