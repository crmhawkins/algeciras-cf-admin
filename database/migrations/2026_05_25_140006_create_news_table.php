<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->json('title');     // translatable
            $table->json('excerpt')->nullable();
            $table->json('body');
            $table->string('cover_image')->nullable();
            $table->string('category', 48)->default('actualidad')->index(); // actualidad/cantera/equipación...
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('news'); }
};
