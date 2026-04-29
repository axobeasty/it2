<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('wiki_pages')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_pages');
    }
};
