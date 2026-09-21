<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! config('permission.teams')) {
            return;
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $teamKey = $columnNames['team_foreign_key'];

        if (! Schema::hasColumn($tableNames['roles'], $teamKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamKey): void {
                $table->unsignedBigInteger($teamKey)->nullable()->after('id');
                $table->index($teamKey, 'roles_team_foreign_key_index');
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique([$teamKey, 'name', 'guard_name']);
            });
        }

        if (! Schema::hasColumn($tableNames['model_has_permissions'], $teamKey)) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use (
                $tableNames,
                $teamKey,
                $pivotPermission,
                $columnNames,
            ): void {
                $table->unsignedBigInteger($teamKey)->default(1);
                $table->index($teamKey, 'model_has_permissions_team_foreign_key_index');

                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign([$pivotPermission]);
                }

                $table->dropPrimary();
                $table->primary(
                    [$teamKey, $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary',
                );

                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign($pivotPermission)
                        ->references('id')
                        ->on($tableNames['permissions'])
                        ->cascadeOnDelete();
                }
            });
        }

        if (! Schema::hasColumn($tableNames['model_has_roles'], $teamKey)) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use (
                $tableNames,
                $teamKey,
                $pivotRole,
                $columnNames,
            ): void {
                $table->unsignedBigInteger($teamKey)->default(1);
                $table->index($teamKey, 'model_has_roles_team_foreign_key_index');

                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign([$pivotRole]);
                }

                $table->dropPrimary();
                $table->primary(
                    [$teamKey, $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary',
                );

                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign($pivotRole)
                        ->references('id')
                        ->on($tableNames['roles'])
                        ->cascadeOnDelete();
                }
            });
        }

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // This migration intentionally leaves the team columns in place once enabled.
    }
};
