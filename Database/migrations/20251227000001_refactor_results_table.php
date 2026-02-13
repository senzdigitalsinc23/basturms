<?php

use Database\Migration;

class RefactorResultsTable20251227000001 extends Migration
{
    public function up(): void
    {
        // Add new columns for SBA and Exam scores
        $this->execute("
            ALTER TABLE results 
            ADD COLUMN sba_score DECIMAL(5,2) DEFAULT 0 AFTER class_id,
            ADD COLUMN exam_score DECIMAL(5,2) DEFAULT 0 AFTER sba_score
        ");
        
        // Migrate existing score data to total_score
        // First, add total_score column
        $this->execute("
            ALTER TABLE results 
            ADD COLUMN total_score DECIMAL(5,2) DEFAULT 0 AFTER exam_score
        ");
        
        // Copy existing score values to total_score
        $this->execute("UPDATE results SET total_score = score");
        
        // Drop the old score column
        $this->execute("ALTER TABLE results DROP COLUMN score");
        
        // Add check constraints
        $this->execute("
            ALTER TABLE results 
            ADD CONSTRAINT chk_sba_score CHECK (sba_score >= 0 AND sba_score <= 50),
            ADD CONSTRAINT chk_exam_score CHECK (exam_score >= 0 AND exam_score <= 50),
            ADD CONSTRAINT chk_total_score CHECK (total_score >= 0 AND total_score <= 100)
        ");
    }

    public function down(): void
    {
        // Remove check constraints
        $this->execute("
            ALTER TABLE results 
            DROP CONSTRAINT IF EXISTS chk_sba_score,
            DROP CONSTRAINT IF EXISTS chk_exam_score,
            DROP CONSTRAINT IF EXISTS chk_total_score
        ");
        
        // Add back the score column
        $this->execute("
            ALTER TABLE results 
            ADD COLUMN score DECIMAL(5,2) DEFAULT 0 AFTER class_id
        ");
        
        // Copy total_score back to score
        $this->execute("UPDATE results SET score = total_score");
        
        // Drop the new columns
        $this->execute("
            ALTER TABLE results 
            DROP COLUMN sba_score,
            DROP COLUMN exam_score,
            DROP COLUMN total_score
        ");
    }
}
