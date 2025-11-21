<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id('complaint_id');

            $table->string('reference_number')->unique(); // رقم مرجعي غير قابل للتعديل

            $table->unsignedBigInteger('user_id'); // المواطن
            $table->unsignedBigInteger('government_entity_id'); // الجهة

            $table->string('type');  
            $table->string('location')->nullable();
            $table->text('description')->nullable();

            $table->enum('status', ['new', 'processing', 'resolved', 'rejected'])
                  ->default('new');

            $table->timestamps();

            // FKs
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->cascadeOnDelete();

            $table->foreign('government_entity_id')
                  ->references('entity_id')->on('government_entities')
                  ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
