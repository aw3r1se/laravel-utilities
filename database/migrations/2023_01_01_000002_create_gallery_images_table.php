<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {

    public function up()
    {
        if (!Schema::hasTable('gallery_images')) {
            Schema::create('gallery_images', function (Blueprint $table) {
                $table->id();
                $table->morphs('model');
                $table->string('folder');
                $table->string('file_name');
                $table->string('alt')->default('');
                $table->string('alt_en')->nullable();
                $table->integer('sort')->default(0)->index();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('gallery_images');
    }
};
