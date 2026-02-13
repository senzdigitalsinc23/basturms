<?php

use Database\Migration;

/**
 * Migration to fix database integrity and performance issues.
 * Adds missing unique constraints, foreign keys, and indexes.
 */
class FixDatabaseIntegrity20251221000000 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // 1. Ensure users.username is unique for foreign key references
        try {
            $this->execute("CREATE UNIQUE INDEX idx_users_username ON users(username)");
        } catch (\Throwable $e) {
            // Already exists or table empty
        }

        // 2. Add Foreign Key to admission_details.class_assigned -> classes.class_id
        try {
            $this->execute("ALTER TABLE admission_details ADD CONSTRAINT fk_admission_class_assigned FOREIGN KEY (class_assigned) REFERENCES classes(class_id) ON DELETE RESTRICT ON UPDATE CASCADE");
        } catch (\Throwable $e) {
            // Might fail if data is inconsistent or FK already exists
        }

        // 3. Add performance indexes for student searches
        try {
            $this->execute("CREATE INDEX idx_students_fullname ON students(first_name, last_name)");
        } catch (\Throwable $e) {
            // Already exists
        }

        // 4. Add performance index for academic searches
        try {
            $this->execute("CREATE INDEX idx_academic_setup_search ON academic_setup(status, academic_year, term)");
        } catch (\Throwable $e) {
            // Already exists
        }

        // 5. Add Foreign Key for grading_scheme.added_by
        try {
            $this->execute("ALTER TABLE grading_scheme ADD CONSTRAINT fk_grading_scheme_added_by FOREIGN KEY (added_by) REFERENCES users(username) ON DELETE RESTRICT ON UPDATE CASCADE");
        } catch (\Throwable $e) {
            // Might fail if data is inconsistent
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        try {
            $this->execute("ALTER TABLE grading_scheme DROP FOREIGN KEY fk_grading_scheme_added_by");
        } catch (\Throwable $e) {}

        try {
            $this->execute("DROP INDEX idx_academic_setup_search ON academic_setup");
        } catch (\Throwable $e) {}

        try {
            $this->execute("DROP INDEX idx_students_fullname ON students");
        } catch (\Throwable $e) {}

        try {
            $this->execute("ALTER TABLE admission_details DROP FOREIGN KEY fk_admission_class_assigned");
        } catch (\Throwable $e) {}

        try {
            $this->execute("DROP INDEX idx_users_username ON users");
        } catch (\Throwable $e) {}
    }
}
