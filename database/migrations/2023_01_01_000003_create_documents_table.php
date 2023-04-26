<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    public function up(): void
    {
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->morphs('model');
                $table->string('folder')->nullable();
                $table->string('file_name')->nullable();
                $table->string('user_name')->default('');
                $table->integer('sort')->index()->default(0);
                
                /** Для документов не хранящихся локально */
                $table->string('url')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
