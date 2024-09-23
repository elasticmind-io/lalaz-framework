<?php

use Lalaz\Data\Migrations\Migration;
use Lalaz\Data\Schema\SchemaBuilder;

class CreatePostsTable extends Migration
{
    private static $tableName = 'users';

    public function up()
    {
        SchemaBuilder::create(static::$tableName, function ($table) {
            // Define your columns here
            $table->increments('id');
            $table->string('email');
            $table->boolean('active');
            $table->text('password');
            $table->timestamps();

            $table->softDeletes()
                ->index(['email', 'active'])
                ->enum('role', ['admin', 'user', 'guest'], false, 'user')
                ->json('metadata');

            $table->unique('email');

        });
    }

    public function down()
    {
        SchemaBuilder::dropIfExists(static::$tableName);
    }
}
