<?php

use Database\Migration;

class CreateFeatureRoleTable20250115000025 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS feature_role (
                id INT AUTO_INCREMENT PRIMARY KEY,
                role_id INT(11) NOT NULL,
                feature_id INT(11) NOT NULL,
                FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS feature_role;");
    }
}
