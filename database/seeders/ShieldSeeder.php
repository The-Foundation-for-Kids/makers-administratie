<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use BezhanSalleh\FilamentShield\Support\Utils;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:Agency","View:Agency","Create:Agency","Update:Agency","Delete:Agency","Restore:Agency","ForceDelete:Agency","ForceDeleteAny:Agency","RestoreAny:Agency","Replicate:Agency","Reorder:Agency","ViewAny:AuthenticationLog","View:AuthenticationLog","Create:AuthenticationLog","Update:AuthenticationLog","Delete:AuthenticationLog","Restore:AuthenticationLog","ForceDelete:AuthenticationLog","ForceDeleteAny:AuthenticationLog","RestoreAny:AuthenticationLog","Replicate:AuthenticationLog","Reorder:AuthenticationLog","ViewAny:Clothing","View:Clothing","Create:Clothing","Update:Clothing","Delete:Clothing","Restore:Clothing","ForceDelete:Clothing","ForceDeleteAny:Clothing","RestoreAny:Clothing","Replicate:Clothing","Reorder:Clothing","ViewAny:Collector","View:Collector","Create:Collector","Update:Collector","Delete:Collector","Restore:Collector","ForceDelete:Collector","ForceDeleteAny:Collector","RestoreAny:Collector","Replicate:Collector","Reorder:Collector","ViewAny:Completed","View:Completed","Create:Completed","Update:Completed","Delete:Completed","Restore:Completed","ForceDelete:Completed","ForceDeleteAny:Completed","RestoreAny:Completed","Replicate:Completed","Reorder:Completed","ViewAny:CreatorHistory","View:CreatorHistory","Create:CreatorHistory","Update:CreatorHistory","Delete:CreatorHistory","Restore:CreatorHistory","ForceDelete:CreatorHistory","ForceDeleteAny:CreatorHistory","RestoreAny:CreatorHistory","Replicate:CreatorHistory","Reorder:CreatorHistory","ViewAny:Creator","View:Creator","Create:Creator","Update:Creator","Delete:Creator","Restore:Creator","ForceDelete:Creator","ForceDeleteAny:Creator","RestoreAny:Creator","Replicate:Creator","Reorder:Creator","ViewAny:Offer","View:Offer","Create:Offer","Update:Offer","Delete:Offer","Restore:Offer","ForceDelete:Offer","ForceDeleteAny:Offer","RestoreAny:Offer","Replicate:Offer","Reorder:Offer","ViewAny:UserInvitation","View:UserInvitation","Create:UserInvitation","Update:UserInvitation","Delete:UserInvitation","Restore:UserInvitation","ForceDelete:UserInvitation","ForceDeleteAny:UserInvitation","RestoreAny:UserInvitation","Replicate:UserInvitation","Reorder:UserInvitation","ViewAny:User","View:User","Create:User","Update:User","Delete:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","View:Dashboard","View:InformatieMaaksters","View:ClothingChart","View:AgencyReport"]},{"name":"geen recht","guard_name":"web","permissions":[]},{"name":"Beheerder","guard_name":"web","permissions":["ViewAny:Agency","View:Agency","Create:Agency","Update:Agency","ViewAny:Clothing","View:Clothing","Create:Clothing","Update:Clothing","Delete:Clothing","Restore:Clothing","ForceDelete:Clothing","ForceDeleteAny:Clothing","RestoreAny:Clothing","Replicate:Clothing","Reorder:Clothing","View:AgencyReport"]},{"name":"Verzamelpunt","guard_name":"web","permissions":["ViewAny:Collector","Update:Collector"]},{"name":"Maakster","guard_name":"web","permissions":["ViewAny:CreatorHistory","View:CreatorHistory","ViewAny:Creator","View:Creator","Update:Creator","ViewAny:Offer","View:Offer","Update:Offer","View:InformatieMaaksters"]},{"name":"Gebruikers beheer","guard_name":"web","permissions":["ViewAny:UserInvitation","View:UserInvitation","Create:UserInvitation","Update:UserInvitation","Delete:UserInvitation","ViewAny:User","View:User","Create:User","Update:User","Delete:User","Restore:User","RestoreAny:User"]},{"name":"Afgehandeld","guard_name":"web","permissions":["ViewAny:Completed","View:Completed","Update:Completed"]}]';
        $directPermissions = '[{"name":"view_agency","guard_name":"web"},{"name":"view_any_agency","guard_name":"web"},{"name":"create_agency","guard_name":"web"},{"name":"update_agency","guard_name":"web"},{"name":"restore_agency","guard_name":"web"},{"name":"restore_any_agency","guard_name":"web"},{"name":"delete_agency","guard_name":"web"},{"name":"delete_any_agency","guard_name":"web"},{"name":"force_delete_agency","guard_name":"web"},{"name":"force_delete_any_agency","guard_name":"web"},{"name":"view_clothing","guard_name":"web"},{"name":"view_any_clothing","guard_name":"web"},{"name":"create_clothing","guard_name":"web"},{"name":"update_clothing","guard_name":"web"},{"name":"restore_clothing","guard_name":"web"},{"name":"restore_any_clothing","guard_name":"web"},{"name":"delete_clothing","guard_name":"web"},{"name":"delete_any_clothing","guard_name":"web"},{"name":"force_delete_clothing","guard_name":"web"},{"name":"force_delete_any_clothing","guard_name":"web"},{"name":"view_collector","guard_name":"web"},{"name":"view_any_collector","guard_name":"web"},{"name":"create_collector","guard_name":"web"},{"name":"update_collector","guard_name":"web"},{"name":"restore_collector","guard_name":"web"},{"name":"restore_any_collector","guard_name":"web"},{"name":"delete_collector","guard_name":"web"},{"name":"delete_any_collector","guard_name":"web"},{"name":"force_delete_collector","guard_name":"web"},{"name":"force_delete_any_collector","guard_name":"web"},{"name":"view_confirm","guard_name":"web"},{"name":"view_any_confirm","guard_name":"web"},{"name":"create_confirm","guard_name":"web"},{"name":"update_confirm","guard_name":"web"},{"name":"restore_confirm","guard_name":"web"},{"name":"restore_any_confirm","guard_name":"web"},{"name":"delete_confirm","guard_name":"web"},{"name":"delete_any_confirm","guard_name":"web"},{"name":"force_delete_confirm","guard_name":"web"},{"name":"force_delete_any_confirm","guard_name":"web"},{"name":"view_creator","guard_name":"web"},{"name":"view_any_creator","guard_name":"web"},{"name":"create_creator","guard_name":"web"},{"name":"update_creator","guard_name":"web"},{"name":"restore_creator","guard_name":"web"},{"name":"restore_any_creator","guard_name":"web"},{"name":"delete_creator","guard_name":"web"},{"name":"delete_any_creator","guard_name":"web"},{"name":"force_delete_creator","guard_name":"web"},{"name":"force_delete_any_creator","guard_name":"web"},{"name":"view_offer","guard_name":"web"},{"name":"view_any_offer","guard_name":"web"},{"name":"create_offer","guard_name":"web"},{"name":"update_offer","guard_name":"web"},{"name":"restore_offer","guard_name":"web"},{"name":"restore_any_offer","guard_name":"web"},{"name":"delete_offer","guard_name":"web"},{"name":"delete_any_offer","guard_name":"web"},{"name":"force_delete_offer","guard_name":"web"},{"name":"force_delete_any_offer","guard_name":"web"},{"name":"view_role","guard_name":"web"},{"name":"view_any_role","guard_name":"web"},{"name":"create_role","guard_name":"web"},{"name":"update_role","guard_name":"web"},{"name":"delete_role","guard_name":"web"},{"name":"delete_any_role","guard_name":"web"},{"name":"view_user","guard_name":"web"},{"name":"view_any_user","guard_name":"web"},{"name":"create_user","guard_name":"web"},{"name":"update_user","guard_name":"web"},{"name":"restore_user","guard_name":"web"},{"name":"restore_any_user","guard_name":"web"},{"name":"delete_user","guard_name":"web"},{"name":"delete_any_user","guard_name":"web"},{"name":"force_delete_user","guard_name":"web"},{"name":"force_delete_any_user","guard_name":"web"},{"name":"widget_ClothingChart","guard_name":"web"},{"name":"widget_CreatorChart","guard_name":"web"},{"name":"page_InformatieMaaksters","guard_name":"web"},{"name":"view_completed","guard_name":"web"},{"name":"view_any_completed","guard_name":"web"},{"name":"create_completed","guard_name":"web"},{"name":"update_completed","guard_name":"web"},{"name":"restore_completed","guard_name":"web"},{"name":"restore_any_completed","guard_name":"web"},{"name":"delete_completed","guard_name":"web"},{"name":"delete_any_completed","guard_name":"web"},{"name":"force_delete_completed","guard_name":"web"},{"name":"force_delete_any_completed","guard_name":"web"},{"name":"view_creator::history","guard_name":"web"},{"name":"view_any_creator::history","guard_name":"web"},{"name":"create_creator::history","guard_name":"web"},{"name":"update_creator::history","guard_name":"web"},{"name":"restore_creator::history","guard_name":"web"},{"name":"restore_any_creator::history","guard_name":"web"},{"name":"delete_creator::history","guard_name":"web"},{"name":"delete_any_creator::history","guard_name":"web"},{"name":"force_delete_creator::history","guard_name":"web"},{"name":"force_delete_any_creator::history","guard_name":"web"}]';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = '';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = collect($rolePlusPermission['permissions'])
                    ->map(fn ($permission) => $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]))
                    ->all();

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
