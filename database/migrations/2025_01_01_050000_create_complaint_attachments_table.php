<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('complaint_attachments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('complaint_id');

            $table->string('file_path');
            $table->enum('type', ['image', 'document'])->default('image');

            $table->timestamps();

            $table->foreign('complaint_id')
                  ->references('complaint_id')->on('complaints')
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_attachments');
    }
};
