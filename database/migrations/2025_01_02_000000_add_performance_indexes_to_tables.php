<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds performance indexes to frequently queried columns
     * to improve query performance and reduce database load.
     */
    public function up(): void
    {
        // Indexes for complaints table
        Schema::table('complaints', function (Blueprint $table) {
            // Index on status - frequently filtered/queried
            $table->index('status', 'idx_complaints_status');
            
            // Index on user_id - foreign key, frequently joined/queried
            $table->index('user_id', 'idx_complaints_user_id');
            
            // Index on government_entity_id - foreign key, frequently filtered
            $table->index('government_entity_id', 'idx_complaints_government_entity_id');
            
            // Index on created_at - frequently used for ordering and date filtering
            $table->index('created_at', 'idx_complaints_created_at');
            
            // Composite index for common query pattern: filtering by entity and status
            $table->index(['government_entity_id', 'status'], 'idx_complaints_entity_status');
        });

        // Indexes for complaint_status_histories table
        Schema::table('complaint_status_histories', function (Blueprint $table) {
            // Index on complaint_id - foreign key, frequently joined
            $table->index('complaint_id', 'idx_complaint_histories_complaint_id');
            
            // Index on handled_by - foreign key, frequently queried
            $table->index('handled_by', 'idx_complaint_histories_handled_by');
            
            // Index on changed_at - frequently used for ordering
            $table->index('changed_at', 'idx_complaint_histories_changed_at');
            
            // Composite index for common query: get latest history by complaint
            $table->index(['complaint_id', 'changed_at'], 'idx_complaint_histories_complaint_changed');
        });

        // Indexes for complaint_attachments table
        Schema::table('complaint_attachments', function (Blueprint $table) {
            // Index on complaint_id - foreign key, frequently joined
            $table->index('complaint_id', 'idx_complaint_attachments_complaint_id');
        });

        // Indexes for users table
        Schema::table('users', function (Blueprint $table) {
            // Index on government_entity_id - foreign key, frequently filtered
            $table->index('government_entity_id', 'idx_users_government_entity_id');
            
            // Index on status - frequently filtered for active/inactive users
            $table->index('status', 'idx_users_status');
            
            // Composite index for common query: get employees by entity
            $table->index(['government_entity_id', 'status'], 'idx_users_entity_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropIndex('idx_complaints_status');
            $table->dropIndex('idx_complaints_user_id');
            $table->dropIndex('idx_complaints_government_entity_id');
            $table->dropIndex('idx_complaints_created_at');
            $table->dropIndex('idx_complaints_entity_status');
        });

        Schema::table('complaint_status_histories', function (Blueprint $table) {
            $table->dropIndex('idx_complaint_histories_complaint_id');
            $table->dropIndex('idx_complaint_histories_handled_by');
            $table->dropIndex('idx_complaint_histories_changed_at');
            $table->dropIndex('idx_complaint_histories_complaint_changed');
        });

        Schema::table('complaint_attachments', function (Blueprint $table) {
            $table->dropIndex('idx_complaint_attachments_complaint_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_government_entity_id');
            $table->dropIndex('idx_users_status');
            $table->dropIndex('idx_users_entity_status');
        });
    }
};


