<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBusinesses extends Migration
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
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
                'null'       => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);

        // InnoDB + utf8mb4 per CLAUDE.md stack rules.
        $this->forge->createTable('businesses', true, [
            'ENGINE'  => 'InnoDB',
            'CHARSET' => 'utf8mb4',
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('businesses', true);
    }
}
