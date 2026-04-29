<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_page_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wiki_page_id')->constrained('wiki_pages')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['wiki_page_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_page_role');
    }
};
