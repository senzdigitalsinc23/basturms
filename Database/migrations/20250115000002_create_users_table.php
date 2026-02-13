<?php

use Database\Migration;

class CreateUsersTable20250115000002 extends Migration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(20) NOT NULL,
                username VARCHAR(20) NOT NULL,
                email VARCHAR(100) DEFAULT NULL,
                password VARCHAR(255) DEFAULT NULL,
                role_id INT(11) DEFAULT NULL,
                status ENUM('active','inactive') DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT NULL,
                is_super_admin TINYINT(1) DEFAULT 0,
                UNIQUE KEY email (email),
                KEY role_id (role_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        // Add foreign key after roles table is expected to exist
        try {
            $this->execute("ALTER TABLE users ADD CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE SET NULL ON UPDATE CASCADE;");
        } catch (\Throwable $e) {
            // If roles table doesn't exist yet, skip FK creation; it can be added in a later migration.
        }
    }

    public function down(): void
    {
        // Drop FK if exists then drop table
        try {
            $this->execute("ALTER TABLE users DROP FOREIGN KEY fk_users_role_id;");
        } catch (\Throwable $e) {
            // ignore
        }
        $this->execute("DROP TABLE IF EXISTS users;");
    }
}
