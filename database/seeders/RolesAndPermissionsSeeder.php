<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions
        $permissions = [
            // Country permissions
            'country.view',
            'country.create',
            'country.update',
            'country.delete',

            // Region permissions
            'region.view',
            'region.create',
            'region.update',
            'region.delete',

            // Institution permissions
            'institution.view',
            'institution.create',
            'institution.update',
            'institution.delete',

            // Analytics permissions
            'analytics.view',
            'analytics.export',

            // Survey permissions
            'survey.view',
            'survey.manage',
            'survey.aggregate',

            // Report permissions
            'report.view',
            'report.generate',

            // Governance score permissions
            'governance.view',
            'governance.recalculate',

            // Audit permissions
            'audit.view',

            // User management
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'user.assign_roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => UserRole::SuperAdmin->value]);
        $superAdmin->syncPermissions(Permission::all());

        $countryAdmin = Role::firstOrCreate(['name' => UserRole::CountryAdmin->value]);
        $countryAdmin->syncPermissions([
            'country.view',
            'region.view', 'region.create', 'region.update', 'region.delete',
            'institution.view', 'institution.create', 'institution.update', 'institution.delete',
            'analytics.view', 'analytics.export',
            'survey.view', 'survey.manage',
            'report.view', 'report.generate',
            'governance.view',
            'audit.view',
            'user.view',
        ]);

        $dataAnalyst = Role::firstOrCreate(['name' => UserRole::DataAnalyst->value]);
        $dataAnalyst->syncPermissions([
            'country.view',
            'region.view',
            'institution.view',
            'analytics.view', 'analytics.export',
            'report.view',
            'governance.view',
        ]);

        $legalReviewer = Role::firstOrCreate(['name' => UserRole::LegalReviewer->value]);
        $legalReviewer->syncPermissions([
            'country.view',
            'institution.view',
            'governance.view',
            'report.view',
            'audit.view',
        ]);

        $advisoryReviewer = Role::firstOrCreate(['name' => UserRole::AdvisoryReviewer->value]);
        $advisoryReviewer->syncPermissions([
            'country.view',
            'region.view',
            'institution.view',
            'analytics.view',
            'report.view',
            'governance.view',
        ]);

        $surveyManager = Role::firstOrCreate(['name' => UserRole::SurveyManager->value]);
        $surveyManager->syncPermissions([
            'country.view',
            'survey.view', 'survey.manage', 'survey.aggregate',
            'analytics.view',
        ]);

        $publicUser = Role::firstOrCreate(['name' => UserRole::PublicUser->value]);
        $publicUser->syncPermissions([
            'country.view',
            'region.view',
            'institution.view',
            'governance.view',
            'report.view',
        ]);

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
