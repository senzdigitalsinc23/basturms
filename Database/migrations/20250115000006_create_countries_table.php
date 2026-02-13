<?php

use Database\Migration;

class CreateCountriesTable20250115000006 extends Migration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS countries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                country_id VARCHAR(5) NOT NULL,
                name VARCHAR(100) NOT NULL,
                cca2 VARCHAR(5) NOT NULL,
                cca3 VARCHAR(5) NOT NULL,
                ccn3 VARCHAR(5) NOT NULL,
                UNIQUE KEY idx_countries_country_id (country_id),
                UNIQUE KEY idx_countries_cca2 (cca2),
                UNIQUE KEY idx_countries_cca3 (cca3)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS countries;");
    }
}
