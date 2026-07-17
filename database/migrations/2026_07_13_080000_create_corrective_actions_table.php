<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * General corrective-action / remark log. Attaches to any form record
 * (maintenance, client_ma, future forms) via a polymorphic subject.
 * Records problems found during an inspection round and how they were resolved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject'); // subject_type + subject_id (+ index)
            $table->string('ref')->nullable();          // จุดที่พบ เช่น "PC-001 / ก.ค."
            $table->text('finding');                    // ปัญหาที่พบ
            $table->text('action')->nullable();         // การแก้ไข/ดำเนินการ
            $table->string('status')->default('resolved'); // resolved | pending
            $table->date('occurred_on')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('created_by_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrective_actions');
    }
};
