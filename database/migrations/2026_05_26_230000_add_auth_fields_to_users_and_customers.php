<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'push_token')) {
                $table->string('push_token')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'profile_image')) {
                $table->string('profile_image')->nullable()->after('push_token');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('profile_image');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            // Asegurarse de que customer puede ser creado sin user_id (registro app)
            // user_id ya es nullable según el seeder
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['push_token', 'profile_image', 'last_login_at']);
        });
    }
};
