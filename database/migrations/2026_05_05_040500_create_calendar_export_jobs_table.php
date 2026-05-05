<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_export_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('job_id')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('calendar_id')->index();
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->json('request_payload');
            $table->json('status_payload')->nullable();
            $table->string('external_job_id')->nullable()->index();
            $table->string('file_url')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_export_jobs');
    }
};
