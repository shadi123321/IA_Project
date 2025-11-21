<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_status_histories', function (Blueprint $table) {
            $table->id('history_id');

            $table->unsignedBigInteger('complaint_id');
            $table->unsignedBigInteger('handled_by')->nullable(); // موظف أو أدمن

            $table->enum('status', ['new', 'processing', 'resolved', 'rejected']);
            $table->text('note')->nullable();

            $table->timestamp('changed_at')->useCurrent();

            // FKs
            $table->foreign('complaint_id')
                  ->references('complaint_id')->on('complaints')
                  ->cascadeOnDelete();

            $table->foreign('handled_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_status_histories');
    }
};
