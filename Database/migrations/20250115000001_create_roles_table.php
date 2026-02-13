<?php

use Database\Migration;

class CreateRolesTable20250115000001 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS roles (
                role_id INT(11) NOT NULL PRIMARY KEY,
                name VARCHAR(30) NOT NULL,
                created_on DATETIME DEFAULT CURRENT_TIMESTAMP,
                created_by INT(11) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS roles;");
    }
}
