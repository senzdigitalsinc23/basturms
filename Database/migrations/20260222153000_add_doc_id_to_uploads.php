<?php

use Database\Migration;

class AddDocIdToUploads20260222153000 extends Migration
{
    public function up(): void
    {
        $this->db->exec("ALTER TABLE uploads ADD COLUMN doc_id VARCHAR(50) NULL AFTER id, ADD INDEX (doc_id)");
    }

    public function down(): void
    {
        $this->db->exec("ALTER TABLE uploads DROP COLUMN doc_id");
    }
}
