<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exclusive_contents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug', 180)->unique();
            $table->string('excerpt', 500)->nullable();
            $table->longText('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->enum('category', ['video', 'noticia', 'descuento', 'evento', 'sorteo'])->index();
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->string('external_url')->nullable();
            $table->string('discount_code', 64)->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exclusive_contents');
    }
};
