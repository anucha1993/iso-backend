<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename the Client "Disk Usage" checklist standard label so the matrix
     * header renders "Disk C: Free GB" instead of the previous "Client GB".
     * Only touches the row if it still matches the seeded label, so any manual
     * admin edits are preserved.
     */
    public function up(): void
    {
        DB::table('checklist_standards')
            ->where('metric_key', 'client_disk_usage')
            ->where('label', 'Client: Disk Usage')
            ->update(['label' => 'Disk C: Free']);
    }

    public function down(): void
    {
        DB::table('checklist_standards')
            ->where('metric_key', 'client_disk_usage')
            ->where('label', 'Disk C: Free')
            ->update(['label' => 'Client: Disk Usage']);
    }
};
