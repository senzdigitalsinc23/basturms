<?php

use Database\Migration;

class CreatePermissionRoleTable20250115000008 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS permission_role (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT(11) DEFAULT NULL,
                permission_id INT(11) DEFAULT NULL,
                UNIQUE KEY role_id (role_id,permission_id),
                KEY permission_id (permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS permission_role;");
    }
}
