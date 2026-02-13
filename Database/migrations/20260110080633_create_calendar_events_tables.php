<?php

use Database\Migration;

/**
 * Migration for creating calendar events and categories tables.
 */
class CreateCalendarEventsTables20260110080633 extends Migration
{
    /**
     * Creates the tables.
     *
     * @return void
     */
    public function up(): void
    {
        // Create calendar_event_categories table
        $this->execute("
            CREATE TABLE IF NOT EXISTS calendar_event_categories (
                event_type_id INT AUTO_INCREMENT PRIMARY KEY,
                event_type_name VARCHAR(100) NOT NULL,
                others TEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Insert default categories
        $this->execute("
            INSERT INTO calendar_event_categories (event_type_name) VALUES 
            ('School event'),
            ('Holidays'),
            ('Examination')
        ");

        // Create calendar_events table
        $this->execute("
            CREATE TABLE IF NOT EXISTS calendar_events (
                event_id INT AUTO_INCREMENT PRIMARY KEY,
                event_title VARCHAR(255) NOT NULL,
                event_category INT NOT NULL,
                event_date DATE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (event_category) REFERENCES calendar_event_categories(event_type_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Drops the tables.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS calendar_events");
        $this->execute("DROP TABLE IF EXISTS calendar_event_categories");
    }
}
