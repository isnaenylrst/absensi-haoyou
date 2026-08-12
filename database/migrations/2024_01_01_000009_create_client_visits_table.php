<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('client_name', 150);
            $table->text('address');
            $table->string('visit_type', 50);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy_m', 8, 2)->nullable();
            $table->text('photo_url');
            $table->text('notes')->nullable();
            $table->timestamp('visited_at');
            $table->enum('review_status', ['wajar', 'perlu_ditinjau'])->default('wajar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_visits');
    }
};
