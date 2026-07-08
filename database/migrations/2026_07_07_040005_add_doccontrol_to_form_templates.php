<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Document-control fields for the form register (shown in the PDF footer /
 * kept for QMR traceability).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            // ระดับชั้นความลับ: general (ข้อมูลทั่วไป) | confidential (ข้อมูลลับ)
            $table->string('confidentiality')->default('general')->after('module_key');
            // รหัสขอแก้ไขเอกสารจาก QMR — stored for reference, NOT shown in the footer
            $table->string('dar_log')->nullable()->after('confidentiality');
            // วันที่บังคับใช้ (effective date) — shown in the footer
            $table->date('effective_date')->nullable()->after('dar_log');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn(['confidentiality', 'dar_log', 'effective_date']);
        });
    }
};
