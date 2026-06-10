<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure roles exist
        $roles = ['admin', 'tesorero', 'secretario', 'observador', 'miembro'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Assign Spatie roles from existing role column
        $users = DB::table('users')->whereNotNull('role')->get(['id', 'role']);
        foreach ($users as $user) {
            $role = Role::where('name', $user->role)->where('guard_name', 'web')->first();
            if ($role) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id'    => $role->id,
                    'model_type' => 'App\Models\User',
                    'model_id'   => $user->id,
                ]);
            }
        }

        // Remove the old role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('email');
        });

        // Restore role column values from Spatie
        $modelHasRoles = DB::table('model_has_roles')
            ->where('model_type', 'App\Models\User')
            ->get(['model_id', 'role_id']);

        foreach ($modelHasRoles as $row) {
            $role = DB::table('roles')->where('id', $row->role_id)->value('name');
            if ($role) {
                DB::table('users')->where('id', $row->model_id)->update(['role' => $role]);
            }
        }
    }
};
