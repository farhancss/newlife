<?php

use App\Models\ContainerPhoto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Distinguish student-uploaded exterior photos from admin-uploaded
     * "received at New Life hub" evidence photos. Existing rows are all
     * student exterior photos, so the column defaults accordingly.
     */
    public function up(): void
    {
        Schema::table('container_photos', function (Blueprint $table): void {
            $table->string('type', 32)
                ->default(ContainerPhoto::TYPE_EXTERIOR)
                ->after('container_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('container_photos', function (Blueprint $table): void {
            $table->dropColumn('type');
        });
    }
};
