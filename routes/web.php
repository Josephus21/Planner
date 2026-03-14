<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeDashboardController;

use App\Http\Controllers\UserController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmployeeRestDayController;

use App\Http\Controllers\PresenceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\TaskController;

use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;

use App\Http\Controllers\FeedController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\PostReactionController;

// ? Projects
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PlannerDashboardController;

// ? Project Categories + Checklist
use App\Http\Controllers\ProjectCategoryController;
use App\Http\Controllers\QualityChecklistController;

use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ProjectBomItemController;

use App\Http\Controllers\JobOrderController;
use App\Http\Controllers\JobOrderDashboardController;

use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\HrManagerDashboardController;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

use App\Http\Controllers\HolidayController;

Route::get('/', function () {
    return view('auth.login');
});

/*
|---------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|---------------------------------------------------------------------------
*/


Route::middleware(['auth'])->group(function () {

    /*
    |---------------------------------------------------------------------------
    | DASHBOARD
    |---------------------------------------------------------------------------
    */

Route::get('/logo/{file}', function ($file) {

    $path = app_path('logos/' . $file);

    if (!File::exists($path)) {
        abort(404);
    }

    return Response::file($path);

});
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    Route::get('/employee-dashboard', [EmployeeDashboardController::class, 'index'])
        ->name('employee.dashboard');

Route::get('/hr/dashboard', [HrManagerDashboardController::class, 'index'])
    ->name('hr.dashboard');

    Route::get('/employee/schedule-events', [EmployeeDashboardController::class, 'scheduleEvents'])
        ->name('employee.schedule.events');


Route::middleware(['auth'])->group(function () {

    Route::get('/sales-orders', [SalesOrderController::class, 'index'])
        ->name('sales-orders.index');
// ? add this
    Route::post('/sales-orders/sync', [SalesOrderController::class, 'sync'])->name('sales.sync');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/job-orders', [JobOrderController::class, 'index'])
        ->name('job-orders.index')
        ->middleware('permission:job_orders.view');
});


Route::middleware(['auth'])->group(function () {
    Route::post('/projects/{project}/bom-items', [ProjectBomItemController::class, 'store'])
    ->name('projects.bom-items.store');

Route::post('/projects/{project}/bom-items/{item}/status', [ProjectBomItemController::class, 'updateStatus'])
    ->name('projects.bom-items.status');

Route::delete('/projects/{project}/bom-items/{item}', [ProjectBomItemController::class, 'destroy'])
    ->name('projects.bom-items.destroy');

Route::post('/projects/{project}/bom-items/reparse', [ProjectBomItemController::class, 'reparse'])
    ->name('projects.bom-items.reparse');
});

Route::get('/job-order-dashboard', [JobOrderDashboardController::class, 'index'])
    ->name('job-orders.dashboard')
    ->middleware(['auth', 'permission:job_orders.view']);

Route::get('/job-order-dashboard/events', [JobOrderDashboardController::class, 'events'])
    ->name('job-orders.dashboard.events')
    ->middleware(['auth', 'permission:job_orders.view']);



Route::get('/manager-dashboard', [ManagerDashboardController::class, 'index'])
    ->name('manager.dashboard')
    ->middleware(['auth']);

Route::get('/manager-dashboard/events', [ManagerDashboardController::class, 'events'])
    ->name('manager.dashboard.events')
    ->middleware(['auth']);


    
    /*
    |---------------------------------------------------------------------------
    | ATTENDANCE (Punch)
    |---------------------------------------------------------------------------
    */
    Route::get('/attendance/punch', function () {
        return redirect()->route('employee.dashboard');
    });

    Route::post('/attendance/punch', [AttendanceController::class, 'punch'])
        ->name('attendance.punch');

    /*
    |---------------------------------------------------------------------------
    | FEED
    |---------------------------------------------------------------------------
    */
    Route::get('/feed', [FeedController::class, 'index'])->name('feed.index');
    Route::post('/feed', [FeedController::class, 'store'])->name('feed.store');

    Route::post('/posts/{post}/comments', [PostCommentController::class, 'store'])
        ->name('posts.comments.store');

    Route::delete('/posts/{post}', [FeedController::class, 'destroy'])
        ->name('posts.destroy');

    Route::post('/posts/{post}/react', [PostReactionController::class, 'toggle'])
        ->name('posts.react');

    /*
    |---------------------------------------------------------------------------
    | ATTENDANCE REPORTS
    |---------------------------------------------------------------------------
    */
    Route::get('/attendance-reports', [AttendanceReportController::class, 'index'])
        ->name('attendance.reports.index')
        ->middleware('permission:attendance_reports.view');

    /*
    |---------------------------------------------------------------------------
    | USERS MANAGEMENT
    |---------------------------------------------------------------------------
    */
    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index')
        ->middleware('permission:users.manage');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->name('users.edit')
        ->middleware('permission:users.manage');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->name('users.update')
        ->middleware('permission:users.manage');

    /*
    |---------------------------------------------------------------------------
    | EMPLOYEES
    |---------------------------------------------------------------------------
    */
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->name('employees.index')
        ->middleware('permission:employees.view');

    Route::get('/employees/create', [EmployeeController::class, 'create'])
        ->name('employees.create')
        ->middleware('permission:employees.create');

    Route::post('/employees', [EmployeeController::class, 'store'])
        ->name('employees.store')
        ->middleware('permission:employees.create');

    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])
        ->name('employees.show')
        ->middleware('permission:employees.view');

    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])
        ->name('employees.edit')
        ->middleware('permission:employees.edit');

    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])
        ->name('employees.update')
        ->middleware('permission:employees.edit');

    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])
        ->name('employees.destroy')
        ->middleware('permission:employees.delete');

    /*
    |---------------------------------------------------------------------------
    | Assign schedule to employee
    |---------------------------------------------------------------------------
    */
    Route::post('/employees/{id}/assign-schedule', [EmployeeController::class, 'assignSchedule'])
        ->name('employees.assignSchedule')
        ->middleware('permission:schedules.edit');

    /*
    |---------------------------------------------------------------------------
    | SCHEDULES
    |---------------------------------------------------------------------------
    */
    Route::get('/schedules', [ScheduleController::class, 'index'])
        ->name('schedules.index')
        ->middleware('permission:schedules.view');

    Route::get('/schedules/create', [ScheduleController::class, 'create'])
        ->name('schedules.create')
        ->middleware('permission:schedules.create');

    Route::post('/schedules', [ScheduleController::class, 'store'])
        ->name('schedules.store')
        ->middleware('permission:schedules.create');

    Route::get('/schedules/{schedule}', [ScheduleController::class, 'show'])
        ->name('schedules.show')
        ->middleware('permission:schedules.view');

    Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])
        ->name('schedules.edit')
        ->middleware('permission:schedules.edit');

    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])
        ->name('schedules.update')
        ->middleware('permission:schedules.edit');

    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])
        ->name('schedules.destroy')
        ->middleware('permission:schedules.delete');

    /*
    |---------------------------------------------------------------------------
    | DEPARTMENTS
    |---------------------------------------------------------------------------
    */
    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index')
        ->middleware('permission:departments.view');

    Route::get('/departments/create', [DepartmentController::class, 'create'])
        ->name('departments.create')
        ->middleware('permission:departments.create');

    Route::post('/departments', [DepartmentController::class, 'store'])
        ->name('departments.store')
        ->middleware('permission:departments.create');

    Route::get('/departments/{department}', [DepartmentController::class, 'show'])
        ->name('departments.show')
        ->middleware('permission:departments.view');

    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
        ->name('departments.edit')
        ->middleware('permission:departments.edit');

    Route::put('/departments/{department}', [DepartmentController::class, 'update'])
        ->name('departments.update')
        ->middleware('permission:departments.edit');

    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
        ->name('departments.destroy')
        ->middleware('permission:departments.delete');

    /*
    |---------------------------------------------------------------------------
    | ROLES
    |---------------------------------------------------------------------------
    */
    Route::get('/roles', [RoleController::class, 'index'])
        ->name('roles.index')
        ->middleware('permission:roles.view');

    Route::get('/roles/create', [RoleController::class, 'create'])
        ->name('roles.create')
        ->middleware('permission:roles.create');

    Route::post('/roles', [RoleController::class, 'store'])
        ->name('roles.store')
        ->middleware('permission:roles.create');

    Route::get('/roles/{role}', [RoleController::class, 'show'])
        ->name('roles.show')
        ->middleware('permission:roles.view');

    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->name('roles.edit')
        ->middleware('permission:roles.edit');

    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->name('roles.update')
        ->middleware('permission:roles.edit');

    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->name('roles.destroy')
        ->middleware('permission:roles.delete');

    /*
    |---------------------------------------------------------------------------
    | PRESENCES
    |---------------------------------------------------------------------------
    */
    Route::get('/presences', [PresenceController::class, 'index'])
        ->name('presences.index')
        ->middleware('permission:presences.view');

    Route::get('/presences/create', [PresenceController::class, 'create'])
        ->name('presences.create')
        ->middleware('permission:presences.create');

    Route::post('/presences', [PresenceController::class, 'store'])
        ->name('presences.store')
        ->middleware('permission:presences.create');

    Route::get('/presences/{presence}', [PresenceController::class, 'show'])
        ->name('presences.show')
        ->middleware('permission:presences.view');

    Route::get('/presences/{presence}/edit', [PresenceController::class, 'edit'])
        ->name('presences.edit')
        ->middleware('permission:presences.edit');

    Route::put('/presences/{presence}', [PresenceController::class, 'update'])
        ->name('presences.update')
        ->middleware('permission:presences.edit');

    Route::delete('/presences/{presence}', [PresenceController::class, 'destroy'])
        ->name('presences.destroy')
        ->middleware('permission:presences.delete');

    /*
    |---------------------------------------------------------------------------
    | PAYROLL
    |---------------------------------------------------------------------------
    */
    Route::get('/payrolls', [PayrollController::class, 'index'])
        ->name('payrolls.index')
        ->middleware('permission:payrolls.view');

    Route::post('/payrolls/generate', [PayrollController::class, 'generate'])
        ->name('payrolls.generate')
        ->middleware('permission:payrolls.create');

    Route::get('/payrolls/{payroll}', [PayrollController::class, 'show'])
        ->name('payrolls.show')
        ->middleware('permission:payrolls.view');

    Route::delete('/payrolls/{payroll}', [PayrollController::class, 'destroy'])
        ->name('payrolls.destroy')
        ->middleware('permission:payrolls.delete');

    /*
    |---------------------------------------------------------------------------
    | HOLIDAYS
    |---------------------------------------------------------------------------
    */
    Route::get('/holidays', [HolidayController::class, 'index'])
        ->name('holidays.index')
        ->middleware('permission:holidays.view');

    Route::get('/holidays/create', [HolidayController::class, 'create'])
        ->name('holidays.create')
        ->middleware('permission:holidays.create');

    Route::post('/holidays', [HolidayController::class, 'store'])
        ->name('holidays.store')
        ->middleware('permission:holidays.create');

    Route::get('/holidays/{holiday}/edit', [HolidayController::class, 'edit'])
        ->name('holidays.edit')
        ->middleware('permission:holidays.edit');

    Route::put('/holidays/{holiday}', [HolidayController::class, 'update'])
        ->name('holidays.update')
        ->middleware('permission:holidays.edit');

    Route::delete('/holidays/{holiday}', [HolidayController::class, 'destroy'])
        ->name('holidays.destroy')
        ->middleware('permission:holidays.delete');

Route::middleware(['auth'])->group(function () {
    Route::get('/employee-rest-days', [EmployeeRestDayController::class, 'index'])->name('employee-rest-days.index');
    Route::get('/employee-rest-days/{employee}/edit', [EmployeeRestDayController::class, 'edit'])->name('employee-rest-days.edit');
    Route::put('/employee-rest-days/{employee}', [EmployeeRestDayController::class, 'update'])->name('employee-rest-days.update');
});
    /*
    |---------------------------------------------------------------------------
    | LEAVE REQUESTS
    |---------------------------------------------------------------------------
    */
    Route::get('/leave-requests', [LeaveRequestController::class, 'index'])
        ->name('leave-requests.index')
        ->middleware('permission:leave_requests.view');

    Route::get('/leave-requests/create', [LeaveRequestController::class, 'create'])
        ->name('leave-requests.create')
        ->middleware('permission:leave_requests.create');

    Route::post('/leave-requests', [LeaveRequestController::class, 'store'])
        ->name('leave-requests.store')
        ->middleware('permission:leave_requests.create');

    Route::get('/leave-requests/{leave_request}', [LeaveRequestController::class, 'show'])
        ->name('leave-requests.show')
        ->middleware('permission:leave_requests.view');

    Route::get('/leave-requests/{leave_request}/edit', [LeaveRequestController::class, 'edit'])
        ->name('leave-requests.edit')
        ->middleware('permission:leave_requests.edit');

    Route::put('/leave-requests/{leave_request}', [LeaveRequestController::class, 'update'])
        ->name('leave-requests.update')
        ->middleware('permission:leave_requests.edit');

    Route::delete('/leave-requests/{leave_request}', [LeaveRequestController::class, 'destroy'])
        ->name('leave-requests.destroy')
        ->middleware('permission:leave_requests.delete');

    Route::get('/leave-requests/confirm/{id}', [LeaveRequestController::class, 'confirm'])
        ->name('leave-requests.confirm')
        ->middleware('permission:leave_requests.confirm');

    Route::get('/leave-requests/reject/{id}', [LeaveRequestController::class, 'reject'])
        ->name('leave-requests.reject')
        ->middleware('permission:leave_requests.reject');

    /*
    |---------------------------------------------------------------------------
    | TASKS
    |---------------------------------------------------------------------------
    */
    Route::get('/tasks', [TaskController::class, 'index'])
        ->name('tasks.index')
        ->middleware('permission:tasks.view');

    Route::get('/tasks/create', [TaskController::class, 'create'])
        ->name('tasks.create')
        ->middleware('permission:tasks.create');

    Route::post('/tasks', [TaskController::class, 'store'])
        ->name('tasks.store')
        ->middleware('permission:tasks.create');

    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])
        ->name('tasks.edit')
        ->middleware('permission:tasks.edit');

    Route::put('/tasks/{task}', [TaskController::class, 'update'])
        ->name('tasks.update')
        ->middleware('permission:tasks.edit');

    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
        ->name('tasks.destroy')
        ->middleware('permission:tasks.delete');

    Route::get('/tasks/done/{task}', [TaskController::class, 'done'])
        ->name('tasks.done')
        ->middleware('permission:tasks.edit');

    Route::get('/tasks/pending/{task}', [TaskController::class, 'pending'])
        ->name('tasks.pending')
        ->middleware('permission:tasks.edit');

    Route::get('/tasks/{task}', [TaskController::class, 'show'])
        ->name('tasks.show')
        ->middleware('permission:tasks.view');

    /*
    |---------------------------------------------------------------------------
    | PROJECT CATEGORIES + QUALITY CHECKLIST (NEW)
    |---------------------------------------------------------------------------
    | Suggested permission keys:
    | project_categories.view, project_categories.create, project_categories.edit, project_categories.delete
    */
    Route::get('/project-categories', [ProjectCategoryController::class, 'index'])
        ->name('project-categories.index')
        ->middleware('permission:project_categories.view');

    Route::get('/project-categories/create', [ProjectCategoryController::class, 'create'])
        ->name('project-categories.create')
        ->middleware('permission:project_categories.create');

    Route::post('/project-categories', [ProjectCategoryController::class, 'store'])
        ->name('project-categories.store')
        ->middleware('permission:project_categories.create');

    Route::get('/project-categories/{project_category}/edit', [ProjectCategoryController::class, 'edit'])
        ->name('project-categories.edit')
        ->middleware('permission:project_categories.edit');

    Route::put('/project-categories/{project_category}', [ProjectCategoryController::class, 'update'])
        ->name('project-categories.update')
        ->middleware('permission:project_categories.edit');

    Route::delete('/project-categories/{project_category}', [ProjectCategoryController::class, 'destroy'])
        ->name('project-categories.destroy')
        ->middleware('permission:project_categories.delete');

    // ? checklist screen
    Route::get('/project-categories/{project_category}/checklists', [ProjectCategoryController::class, 'checklists'])
        ->name('project-categories.checklists')
        ->middleware('permission:project_categories.view');

    // ? checklist items CRUD
    Route::post('/project-categories/{project_category}/checklists', [QualityChecklistController::class, 'store'])
        ->name('checklists.store')
        ->middleware('permission:project_categories.edit');

    Route::put('/checklists/{quality_checklist}', [QualityChecklistController::class, 'update'])
        ->name('checklists.update')
        ->middleware('permission:project_categories.edit');

    Route::delete('/checklists/{quality_checklist}', [QualityChecklistController::class, 'destroy'])
        ->name('checklists.destroy')
        ->middleware('permission:project_categories.delete');

    /*
    |---------------------------------------------------------------------------
    | PROJECTS (NEW)
    |---------------------------------------------------------------------------
    | Keys:
    | projects.view, projects.create, projects.edit, projects.delete
    | project_updates.create
    */
    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('projects.index')
        ->middleware('permission:projects.view');

    Route::get('/projects/create', [ProjectController::class, 'create'])
        ->name('projects.create')
        ->middleware('permission:projects.create');

    Route::post('/projects', [ProjectController::class, 'store'])
        ->name('projects.store')
        ->middleware('permission:projects.create');

    Route::get('/projects/{project}', [ProjectController::class, 'show'])
        ->name('projects.show')
        ->middleware('permission:projects.view');

    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])
        ->name('projects.edit')
        ->middleware('permission:projects.edit');

    Route::put('/projects/{project}', [ProjectController::class, 'update'])
        ->name('projects.update')
        ->middleware('permission:projects.edit');

    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])
        ->name('projects.destroy')
        ->middleware('permission:projects.delete');

    // ? single progress route only (remove duplicates)
    Route::post('/projects/{project}/progress', [ProjectController::class, 'addProgress'])
        ->name('projects.updateProgress')
        ->middleware('permission:project_updates.create');

       Route::post('/quality-checklists/{checklist}/done', [QualityChecklistController::class, 'done'])
    ->name('quality-checklists.done')
    ->middleware('permission:projects.edit'); // or projects.view if you prefer
    /*
    |---------------------------------------------------------------------------
    | PLANNER DASHBOARD
    |---------------------------------------------------------------------------
    */
    Route::get('/planner-dashboard', [PlannerDashboardController::class, 'index'])
        ->name('planner.dashboard')
        ->middleware('permission:projects.view');

    Route::get('/planner-dashboard/events', [PlannerDashboardController::class, 'events'])
        ->name('planner.dashboard.events')
        ->middleware('permission:projects.view');




        Route::get('/vehicles', [VehicleController::class, 'index'])
    ->name('vehicles.index')
    ->middleware('permission:vehicles.view');

Route::get('/vehicles/create', [VehicleController::class, 'create'])
    ->name('vehicles.create')
    ->middleware('permission:vehicles.create');

Route::post('/vehicles', [VehicleController::class, 'store'])
    ->name('vehicles.store')
    ->middleware('permission:vehicles.create');

Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])
    ->name('vehicles.edit')
    ->middleware('permission:vehicles.edit');

Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])
    ->name('vehicles.update')
    ->middleware('permission:vehicles.edit');

Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])
    ->name('vehicles.destroy')
    ->middleware('permission:vehicles.delete');
    /*
    |---------------------------------------------------------------------------
    | PROFILE
    |---------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';