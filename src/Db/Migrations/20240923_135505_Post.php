<?php

use Lalaz\Data\Migrations\Migration;
use Lalaz\Data\Schema\SchemaBuilder;

class Post extends Migration
{
    private static $tableName = 'table_name';

    public function up()
    {
        SchemaBuilder::create(static::$tableName, function ($table) {
            // Define your columns here
            $table->increments('id');
            $table->string('column_name');
            $table->timestamps();
        });
    }

    public function down()
    {
        SchemaBuilder::dropIfExists(static::$tableName);
    }
}
