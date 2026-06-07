# Changelog

All significant changes to this project will be documented in this file.

## [2.0.0] - 2025-12-22

### Added

-   **Schema:** `GrammarResolver` and `Schema::forConnection()` to centralize grammar selection.
-   **QueryBuilder:** configurable empty `whereIn`/`whereNotIn` behavior.
-   **Docs/typing:** richer PHPDoc generics for ModelQuery/relations and query builder return shapes.

### Changed

-   **QueryBuilder:** improved alias handling for `select/value/pluck` and `table.*` wrapping.
-   **Schema:** SQLite migration table DDL now uses SQLite-specific syntax.

### Fixed

-   **SoftDeletes:** unsaved models no longer insert on delete.
-   **ModelQuery:** methods that return values now return the builder result (not always `$this`).
-   **Schema:** unified MySQL grammar to a single canonical class.

### Tests

-   Expanded coverage across QueryBuilder, Schema/Grammar, migrations, and transactions.

## [1.6.0] - 2025-12-22

### Added

-   **CLI db:wipe:** Drop all tables in the database with confirmation and `--force`.
-   **CLI db:truncate:** Remove all data except the migrations table, with confirmation and `--force`.
-   **CLI tests:** Coverage for wipe and truncate commands.

### Changed

-   CLI output standardized on `echo` for stdout; errors remain on stderr.

## [1.5.0] - 2025-12-21

### Added

-   **Seeders:** New seeding system with `Seeder`, `SeederRunner`, and `SeedPathResolver`.
-   **CLI seed commands:** `seed` and `make:seed` with configurable default path (`./database/seeds`).
-   **Seeder tests:** Coverage for runner behavior, path resolver, and CLI commands.
-   **Docs:** README updated with seeder usage and configuration examples.

### Changed

-   CLI seed command output now uses stdout consistently for testability.

## [1.4.3] - 2025-12-10

### Fixed

-   Transaction wrapper now checks `inTransaction()` before commit/rollback to avoid "no active transaction" errors when drivers auto-commit DDL.
-   `$table->id()` auto-assigns a primary key only when none exists, keeping AUTO_INCREMENT valid on MySQL.
-   Column modifiers `->unique()` / `->primary()` are now emitted inline for MySQL/SQLite grammars, so `$table->string('email')->unique()` creates the constraint.

## [1.4.2] - 2025-12-10

### Changed

-   **CLI make:migration:** Migration names must now be CamelCase Latin words (e.g., `CreateUsersTable`); invalid names are rejected with an error.

## [1.4.1] - 2025-12-10

### Added

-   **CLI tests:** Added coverage for skipping already-ran migrations and for migration path resolver uniqueness/default path handling.

### Fixed

-   **Migrator:** Pending detection now compares against ran migration names, preventing repeat execution of logged migrations.
-   **CLI path resolver:** Allows registering default migration path even if the directory does not exist yet and deduplicates entries.

## [1.4.0] - 2025-12-10

### Added

-   **ORM test coverage:** Added PHPUnit tests for Model, ModelQuery, ModelCollection, SoftDeletes, and all relation types; seeded fake profiles/roles for relation scenarios.
-   **In-memory fakes:** FakeConnection now exposes table storage; FakeQueryBuilder emulates where/null filters, pivot joins, pagination counters, and CRUD without PDO.

### Fixed

-   Eliminated PHPUnit notice in Migrator tests by stubbing `MigrationPathResolver`.
-   Fake query builder signatures now align with `QueryBuilderInterface` (return types, count/exists) to avoid compatibility errors during tests.

## [1.3.0] – 2025-12-10

### Added

-   **Full ORM layer (ActiveRecord/Eloquent-like):**

    -   `Model`, `ModelQuery`, `ModelCollection`
    -   Mass assignment: `$fillable`, `$guarded`, `$hidden`
    -   Attribute casting: `int`, `float`, `bool`, `string`, `array`, `json`, `datetime`, `date`
    -   Automatic timestamps (`created_at`, `updated_at`)
    -   Core CRUD API:
        -   `create()`
        -   `save()`
        -   `delete()`
        -   `find()`
        -   `all()`
    -   Hydration system:
        -   `hydrate()`
        -   automatic hydration for relations
        -   lazy-loading with magic `__get()`
    -   Eager loading support via `$model->load('relation')`

-   **Relationships system:**

    -   `HasOne`
    -   `HasMany`
    -   `BelongsTo`
    -   `BelongsToMany`
    -   Automatic resolution of:
        -   foreign keys
        -   owner keys
        -   pivot tables
    -   Pivot table join support

-   **SoftDeletes trait:**

    -   `deleted_at` column support
    -   `trashed()`
    -   `restore()`
    -   Query helpers:
        -   `withTrashed()`
        -   `withoutTrashed()`
        -   `onlyTrashed()`

-   **Model helpers**
    -   `getQualifiedKeyName()`
    -   `getForeignKey()`
    -   `joiningTable()`

### Changed

-   Rewritten ORM interaction layer to use:
    -   `QueryBuilder` with correct namespacing
    -   `insertGetId`, `insert`, `update`, `delete` with full QueryBuilder compatibility
-   Standardized relationship hydration logic
-   Updated delete logic to integrate SoftDeletes when trait is used

### Fixed

-   Incorrect namespace import of QueryBuilder inside `Model.php`
-   Missing `getQualifiedKeyName()` method causing relation/primary key resolution issues
-   Incorrect usage of `$rows->toArray()` in relations (now uses hydrate)
-   Timestamps no longer rely on non-existent helper `now()`
-   SoftDeletes no longer causes Intelephense errors via PHPDoc method declaration

## [1.2.0] – 2025-12-10

### Added

-   **ORM layer (ActiveRecord-style Model)**:

    -   `Codemonster\Database\Model`
    -   `ModelQuery` with model hydration
    -   `ModelCollection` with helper methods

-   **Relationships system**:

    -   Base `Relation` class
    -   `HasOne`, `HasMany`, `BelongsTo`, `BelongsToMany`

-   **Model features**:
    -   `$fillable`, `$guarded`, `$hidden`
    -   `$casts` with primitive and datetime casting
    -   `timestamps` support (`created_at`, `updated_at`)
    -   Soft deletes via `SoftDeletes` trait (`deleted_at`)
    -   Lazy loading of relations (`$user->posts`)
    -   Eager loading via `$user->load('posts')` и `$collection->load()`

## [1.1.0] – 2025-12-09

### Added

-   **Multi-driver connection support**

    -   Added `driver` option (`mysql`, `sqlite`)
    -   Automatic DSN selection based on driver
    -   SQLite in-memory support (`sqlite::memory:`)

-   **Schema Grammars System**

    -   New extensible `Grammar` architecture
    -   Implemented `MySqlGrammar`
    -   Implemented `SQLiteGrammar`
    -   Added implementations for:
        -   `compileCreate`
        -   `compileDrop`
        -   `compileDropIfExists`
        -   `compileAlter`
        -   `compileRenameTable`
        -   `compileColumn`
        -   `compileForeign`
        -   `compileInlineForeign`

-   **Improved Blueprint & ColumnDefinition**
    -   Full type map with consistent options (`length`, `precision`, `scale`)
    -   Unified modifiers: `nullable`, `default`, `unsigned`, `autoIncrement`, `comment`, `primary`, `unique`, `change`

### Changed

-   `Connection`:
    -   Requires `driver` explicitly
    -   DSN now depends on the selected driver
    -   Returns a `Schema` instance bound to the proper grammar
-   Schema Builder:
    -   SQL compilation fully delegated to driver-specific grammars
    -   CREATE / ALTER / DROP return multiple SQL statements
-   Test suite updated:
    -   FakeConnection adjusted to new contract
    -   QueryBuilder, Schema, Migration tests aligned with new behavior

### Fixed

-   Fixed method signature mismatches in Intelephense
-   Corrected SQLite behavior and missing type mappings
-   Fixed SchemaHelper in support package
-   Repaired ColumnDefinition attributes (`length`, `precision`, `scale`, `unsigned`)

### Removed

-   Legacy single-driver DSN builder
-   Deprecated schema compiler code no longer part of the package

## [1.0.0] – 2025-12-08

### Added

-   **Stabilized Query Builder API**

    -   Finalized interfaces (`QueryBuilderInterface`, `ConnectionInterface`)
    -   Unified SQL compiler architecture
    -   Added reliable aggregate handling without mutating the builder
    -   Added consistent `toSql()` + bindings output across all query types

-   **DatabaseManager**

    -   Multiple connections support
    -   Lazy connection initialization
    -   Full config-based connection resolver
    -   `connection(name)` API

-   **Connection layer**

    -   Strict error handling via `QueryException` / `DatabaseException`
    -   Dedicated `transaction()` wrapper with automatic rollback on failure
    -   Strong return types and full interface coverage

-   **Schema Builder**

    -   Table creation, modification and drop operations
    -   Full set of column types (integer families, text, JSON, datetime, boolean, UUID etc.)
    -   Index builder (primary, unique, index)
    -   Foreign key constraints

-   **Migration System**

    -   Migration repository with automatic table creation
    -   Migration runner with per-migration transactions
    -   Rollback & status commands
    -   Migration file resolver with multi-directory support

-   **Standalone CLI**

    -   `database migrate`
    -   `database migrate:rollback`
    -   `database migrate:status`
    -   `database make:migration`
    -   Autodiscovery of `database/migrations` directory

-   **Test Suite (full coverage)**
    -   FakeConnection for integration-like testing
    -   QueryBuilder grammar tests (select, joins, where, aggregates, pagination)
    -   Schema grammar tests
    -   MigrationRepository & Migrator tests
    -   Connection behavior tests (prepare, exceptions, transactions)

### Changed

-   Significantly refactored internal SQL grammar:
    -   Unified compiler pipeline for all query types
    -   Improved ORDER BY / GROUP BY / HAVING positioning rules
    -   More consistent parameter binding logic
-   Rewritten migration runner to use explicit transactions
-   Normalized naming conventions across all components (Builder, Grammar, Repository)
-   Simplified QueryBuilder internals using condition and join objects (`WhereCondition`, `WhereGroup`, `JoinClause`)
-   Improved developer experience for `insertGetId()`, `exists()`, `pluck()`, and pagination methods

### Fixed

-   Nested where groups now compile with correct parentheses and precedence
-   Raw expressions are no longer escaped incorrectly
-   Pagination SQL no longer mutates the original builder state
-   JOIN compiler now respects ordering and nested conditions
-   Connection::table() now properly returns a query builder implementing the contract
-   Numerous edge-case bugs found during test suite completion

### Removed

-   All deprecated pre-0.7.0 grammar logic
-   Old connection helpers replaced with a typed, interface-driven API
-   Legacy QueryBuilder internals rewritten or removed

## [0.7.0] – 2025-12-01

### Added

-   Major QueryBuilder upgrade:
    -   Nested where-groups using closures
    -   whereRaw(), orWhereRaw()
    -   selectRaw(), orderByRaw()
    -   whereIn / whereNotIn / whereBetween / whereNotBetween / whereNull / whereNotNull
    -   JOIN system (innerJoin, leftJoin, rightJoin, crossJoin)
    -   DISTINCT support
    -   GROUP BY, HAVING, havingRaw()
    -   Aggregates: count(), sum(), avg(), min(), max()
    -   exists(), doesntExist()
    -   value(), pluck(), forPage(), simplePaginate()
-   Full SQL compiler implementation for:
    -   joins
    -   nested groups
    -   raw expressions
    -   complex conditions
    -   between/not between
    -   group/having order & priority
-   Added `RawExpression` class to support raw SQL segments

### Changed

-   Reworked WHERE compiler (now supports full tree-based logic)
-   Improved compileSelect() order: joins → where → group → having → order → limit → offset
-   Normalized SQL generation for consistent MySQL output
-   select() now accepts both strings and array of strings
-   orderByRaw merged into unified ORDER BY compiler

### Fixed

-   DISTINCT, GROUP BY, HAVING now compiled correctly
-   join() and whereRaw() functionality works fully
-   BETWEEN and NOT BETWEEN handled properly
-   ORDER BY raw expressions no longer wrapped incorrectly

## [0.6.0] – 2025-11-30

### Added

-   Fully featured migration system:
    -   `Migration` base class
    -   `MigrationRepository` for tracking applied migrations
    -   `Migrator` with support for batches, rollback, and status
    -   `MigrationPathResolver` for flexible multi-path migration loading
-   Standalone CLI tooling:
    -   `vendor/bin/database migrate`
    -   `vendor/bin/database migrate:rollback`
    -   `vendor/bin/database migrate:status`
    -   `vendor/bin/database make:migration`
    -   CLI kernel (`DatabaseConsoleKernel`) and command registry
-   Automatic CLI integration support for external frameworks (e.g. Codemonster Annabel)
-   Support for multiple migration directories (modules, packages, custom paths)
-   Improved Schema Builder:
    -   Extended column types (decimal, float, double, char, json, mediumText, longText, datetime, uuid, etc.)
    -   Foreign key creation inside CREATE TABLE and ALTER TABLE
    -   Index creation/removal during schema changes

### Changed

-   Standardized Grammar output: all schema operations now return arrays of statements
-   Improved `bin/database` for standalone usage and dynamic config loading
-   Updated README with full documentation for Query Builder, Schema Builder, migrations, and CLI usage

### Fixed

-   IDE warnings for undefined global helpers using safe stub declarations
-   Missing return values in CLI helper functions

## [0.5.0] – 2025-11-29

### Added

-   Expanded Schema Builder with full support for MySQL data types:
    -   Integer types: `bigInteger`, `mediumInteger`, `smallInteger`, `tinyInteger`
    -   Decimal & floating types: `decimal`, `double`, `float`
    -   String & text types: `char`, `mediumText`, `longText`
    -   JSON type: `json`
    -   Date & time types: `date`, `datetime`, `time`, `year`
    -   UUID type: `uuid`
-   Added modifier support for:
    -   `unsigned`
    -   `autoIncrement`
    -   `comment`
    -   `change()` for modifying existing columns

### Changed

-   Reworked `compileType()` in `MySqlGrammar` to support all new types.
-   Updated `Blueprint` to include methods for new column types.
-   Standardized Schema Builder to return array of SQL statements for all operations.
-   Improved overall consistency of CREATE/ALTER/DROP SQL generation.

### Fixed

-   Removed obsolete `$commands` from `Blueprint`.
-   Fixed handling of NOT NULL / NULL defaults.
-   Correct handling of PRIMARY/UNIQUE via indexes instead of column modifiers.

## [0.4.0] - 2025-11-21

### Added

-   Transaction support in `Connection` and `ConnectionInterface`.
-   Added methods:
-   `beginTransaction()`
-   `commit()`
-   `rollBack()`
-   `transaction(callable $callback)`
-   The `transaction()` method allows performing multiple operations within a single transaction with automatic `commit` or `rollback` on exception.

## [0.3.0] - 2025-11-21

### Added

-   Implemented support for `INSERT`, `UPDATE`, and `DELETE` in `QueryBuilder`.
-   Added new methods:
-   `insert(array $values)` — insert a row.
-   `insertGetId(array $values)` — insert a row and return its ID.
-   `update(array $values)` — update rows based on conditions.
-   `delete()` — delete rows.
-   Implemented SQL compilers:
-   `compileInsert()`
-   `compileUpdate()`
-   `compileDelete()`
-   Added internal binding extensions, including WHERE, ORDER BY, and LIMIT/OFFSET handling for updates and deletes.

## [0.2.0] – 2025-11-20

### Added

-   Implemented basic `QueryBuilder`:
-   `table()` for creating a new builder
-   `select()` for specifying selectable columns
-   `where()` / `orWhere()` for conditions
-   `orderBy()` for sorting
-   `limit()` and `offset()` for pagination
-   `get()` and `first()` for executing SELECT queries
-   `toSql()` and `getBindings()` for debugging
-   Added the `table()` method to `Connection` and `ConnectionInterface`.
-   Support for building SQL with placeholders and automatic bindings.

## [0.1.0] – 2025-11-19

### Added

-   Basic architecture of the `codemonster-ru/database` package.
-   Support for connecting to MySQL via PDO.
-   `Connection` class with methods:
-   `select()`
-   `selectOne()`
-   `insert()`
-   `update()`
-   `delete()`
-   `statement()`
-   Implemented `DatabaseManager` with support for multiple connections.
-   `QueryException` exception for PDO errors.
-   `ConnectionInterface` interface.
-   Prepared the structure for the future QueryBuilder (`Query/QueryBuilder.php`).
