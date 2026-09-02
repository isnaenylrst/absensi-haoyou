```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->decimal('meal_rate', 12, 2)
                ->default(10000)
                ->after('alpa_deduction_per_day');

            $table->decimal('transport_rate', 12, 2)
                ->default(10000)
                ->after('meal_rate');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn([
                'meal_rate',
                'transport_rate',
            ]);
        });
    }
};

