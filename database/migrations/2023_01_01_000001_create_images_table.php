<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    public function up()
    {
        if (!Schema::hasTable('images')) {
            Schema::create('images', function (Blueprint $table) {
                $table->id();
                $table->morphs('model');
                $table->string('folder');
                $table->string('file_name');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('images');
    }
};
