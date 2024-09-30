<?php declare(strict_types=1);

namespace Lalaz\Data\Migrations;

/**
 * Class Migration
 *
 * This abstract class serves as the base for all database migration classes within the application.
 * It defines the structure that all migrations should follow by requiring the implementation
 * of the `up()` and `down()` methods. The `up()` method should contain the logic to apply the migration,
 * such as creating tables or adding columns, while the `down()` method should reverse these changes,
 * allowing for rollback functionality.
 *
 * @package Lalaz\Data\Migrations
 */
abstract class Migration
{
    /**
     * Applies the migration changes to the database.
     *
     * This method should contain the code necessary to implement the migration,
     * such as creating new tables, adding columns, or modifying existing structures.
     *
     * @return void
     */
    abstract public function up();

    /**
     * Reverts the migration changes from the database.
     *
     * This method should undo the changes made in the `up()` method,
     * allowing the database to return to its previous state before the migration was applied.
     *
     * @return void
     */
    abstract public function down();
}
