<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
      public function up(): void
    {
        Schema::create('government_entities', function (Blueprint $table) {
            $table->id('entity_id'); // Primary Key
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('location')->nullable(); // optional
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('government_entities');
    }
};
