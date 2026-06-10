<?php

namespace Codemonster\Database\Migrations;

abstract class Migration
{
    /**
     * Apply the migration.
     */
    abstract public function up(): void;

    /**
     * Reverse the migration.
     */
    abstract public function down(): void;
}
