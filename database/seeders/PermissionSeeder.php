<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [

            /*
            |--------------------------------------------------------------------------
            | Dashboard
            |--------------------------------------------------------------------------
            */
            ['key' => 'dashboard.view', 'label' => 'View Dashboard'],

            /*
            |--------------------------------------------------------------------------
            | Employees
            |--------------------------------------------------------------------------
            */
            ['key' => 'employees.view',   'label' => 'View Employees'],
            ['key' => 'employees.create', 'label' => 'Create Employees'],
            ['key' => 'employees.edit',   'label' => 'Edit Employees'],
            ['key' => 'employees.delete', 'label' => 'Delete Employees'],

            /*
            |--------------------------------------------------------------------------
            | Roles
            |--------------------------------------------------------------------------
            */
            ['key' => 'roles.view',   'label' => 'View Roles'],
            ['key' => 'roles.create', 'label' => 'Create Roles'],
            ['key' => 'roles.edit',   'label' => 'Edit Roles'],
            ['key' => 'roles.delete', 'label' => 'Delete Roles'],

            /*
            |--------------------------------------------------------------------------
            | Departments
            |--------------------------------------------------------------------------
            */
            ['key' => 'departments.view',   'label' => 'View Departments'],
            ['key' => 'departments.create', 'label' => 'Create Departments'],
            ['key' => 'departments.edit',   'label' => 'Edit Departments'],
            ['key' => 'departments.delete', 'label' => 'Delete Departments'],

            /*
            |--------------------------------------------------------------------------
            | Tasks
            |--------------------------------------------------------------------------
            */
            ['key' => 'tasks.view',   'label' => 'View Tasks'],
            ['key' => 'tasks.create', 'label' => 'Create Tasks'],
            ['key' => 'tasks.edit',   'label' => 'Edit Tasks'],
            ['key' => 'tasks.delete', 'label' => 'Delete Tasks'],

            /*
            |--------------------------------------------------------------------------
            | Projects
            |--------------------------------------------------------------------------
            */
            ['key' => 'projects.view',   'label' => 'View Projects'],
            ['key' => 'projects.create', 'label' => 'Create Projects'],
            ['key' => 'projects.edit',   'label' => 'Edit Projects'],
            ['key' => 'projects.delete', 'label' => 'Delete Projects'],

            ['key' => 'project_updates.create', 'label' => 'Update Project Progress'],

            /*
            |--------------------------------------------------------------------------
            | Project Categories + Quality Checklist (NEW)
            |--------------------------------------------------------------------------
            */
            ['key' => 'project_categories.view',   'label' => 'View Project Categories'],
            ['key' => 'project_categories.create', 'label' => 'Create Project Categories'],
            ['key' => 'project_categories.edit',   'label' => 'Edit Project Categories'],
            ['key' => 'project_categories.delete', 'label' => 'Delete Project Categories'],

            // OPTIONAL: if you want separate permissions later
            // ['key' => 'quality_checklists.view',   'label' => 'View Quality Checklists'],
            // ['key' => 'quality_checklists.manage', 'label' => 'Manage Quality Checklist Items'],

            /*
            |--------------------------------------------------------------------------
            | Presences
            |--------------------------------------------------------------------------
            */
            ['key' => 'presences.view',   'label' => 'View Presences'],
            ['key' => 'presences.create', 'label' => 'Create Presences'],
            ['key' => 'presences.edit',   'label' => 'Edit Presences'],
            ['key' => 'presences.delete', 'label' => 'Delete Presences'],

            /*
            |--------------------------------------------------------------------------
            | Payrolls
            |--------------------------------------------------------------------------
            */
            ['key' => 'payrolls.view',   'label' => 'View Payrolls'],
            ['key' => 'payrolls.create', 'label' => 'Create Payrolls'],
            ['key' => 'payrolls.edit',   'label' => 'Edit Payrolls'],
            ['key' => 'payrolls.delete', 'label' => 'Delete Payrolls'],

            /*
            |--------------------------------------------------------------------------
            | Leave Requests
            |--------------------------------------------------------------------------
            */
            ['key' => 'leave_requests.view',    'label' => 'View Leave Requests'],
            ['key' => 'leave_requests.create',  'label' => 'Create Leave Requests'],
            ['key' => 'leave_requests.edit',    'label' => 'Edit Leave Requests'],
            ['key' => 'leave_requests.delete',  'label' => 'Delete Leave Requests'],
            ['key' => 'leave_requests.confirm', 'label' => 'Confirm Leave Requests'],
            ['key' => 'leave_requests.reject',  'label' => 'Reject Leave Requests'],

            /*
            |--------------------------------------------------------------------------
            | Schedules
            |--------------------------------------------------------------------------
            */
            ['key' => 'schedules.view',   'label' => 'View Schedules'],
            ['key' => 'schedules.create', 'label' => 'Create Schedules'],
            ['key' => 'schedules.edit',   'label' => 'Edit Schedules'],
            ['key' => 'schedules.delete', 'label' => 'Delete Schedules'],

            /*
            |--------------------------------------------------------------------------
            | Attendance Reports
            |--------------------------------------------------------------------------
            */
            ['key' => 'attendance_reports.view',   'label' => 'View Attendance Reports'],
            ['key' => 'attendance_reports.create', 'label' => 'Create Attendance Reports'],
            ['key' => 'attendance_reports.edit',   'label' => 'Edit Attendance Reports'],
            ['key' => 'attendance_reports.delete', 'label' => 'Delete Attendance Reports'],

            /*
            |--------------------------------------------------------------------------
            | News Feed / Social
            |--------------------------------------------------------------------------
            */
            ['key' => 'feed.view', 'label' => 'View News Feed'],

            ['key' => 'posts.create',        'label' => 'Create Posts'],
            ['key' => 'posts.edit_own',      'label' => 'Edit Own Posts'],
            ['key' => 'posts.delete_own',    'label' => 'Delete Own Posts'],
            ['key' => 'posts.delete_any',    'label' => 'Delete Any Post (Moderator)'],

            ['key' => 'comments.create',     'label' => 'Create Comments'],
            ['key' => 'comments.edit_own',   'label' => 'Edit Own Comments'],
            ['key' => 'comments.delete_own', 'label' => 'Delete Own Comments'],
            ['key' => 'comments.delete_any', 'label' => 'Delete Any Comment'],

            ['key' => 'reactions.create',    'label' => 'React to Posts'],

['key' => 'vehicles.view',   'label' => 'View Vehicles'],
['key' => 'vehicles.create', 'label' => 'Create Vehicles'],
['key' => 'vehicles.edit',   'label' => 'Edit Vehicles'],
['key' => 'vehicles.delete', 'label' => 'Delete Vehicles'],

/*
|--------------------------------------------------------------------------
| BOM Items (NEW)
|--------------------------------------------------------------------------
*/
['key' => 'bom_items.create', 'label' => 'Add BOM Items'],
['key' => 'bom_items.delete', 'label' => 'Delete BOM Items'],
['key' => 'bom_items.update_status', 'label' => 'Update BOM Item Status'],


/*
|--------------------------------------------------------------------------
| Job Orders (LFP - DPOD)  (NEW)
|--------------------------------------------------------------------------
*/
['key' => 'job_orders.view', 'label' => 'View Job Orders (LFP - DPOD)'],
['key' => 'job_orders.sync', 'label' => 'Sync Job Orders from API'],
/*
|--------------------------------------------------------------------------
| Sales Orders (NEW)
|--------------------------------------------------------------------------
*/
['key' => 'sales_orders.view', 'label' => 'View Sales Orders'],
['key' => 'sales_orders.sync', 'label' => 'Sync Sales Orders from API'],
            /*
            |--------------------------------------------------------------------------
            | Users Management
            |--------------------------------------------------------------------------
            */
            ['key' => 'users.manage', 'label' => 'Manage Users & Permissions'],
        ];

      foreach ($perms as $p) {
    Permission::updateOrCreate(
        ['key' => $p['key']],  // use 'key' here
        ['label' => $p['label']]
    );
}
    }
}