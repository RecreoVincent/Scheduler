<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\Ms365StudentAccountController;
use App\Http\Controllers\Dean\DashboardController as DeanDashboardController;
use App\Http\Controllers\Dean\InstructorController as DeanInstructorController;
use App\Http\Controllers\Dean\InstructorUnitController as DeanInstructorUnitController;
use App\Http\Controllers\Dean\PrintController as DeanPrintController;
use App\Http\Controllers\Dean\RoomController as DeanRoomController;
use App\Http\Controllers\Dean\ScheduleArchiveController as DeanScheduleArchiveController;
use App\Http\Controllers\Dean\ScheduleController as DeanScheduleController;
use App\Http\Controllers\Dean\SectionController as DeanSectionController;
use App\Http\Controllers\Dean\SettingsController as DeanSettingsController;
use App\Http\Controllers\Dean\StudentController as DeanStudentController;
use App\Http\Controllers\Dean\SubjectAssignmentController as DeanSubjectAssignmentController;
use App\Http\Controllers\Dean\SubjectController as DeanSubjectController;
use App\Http\Controllers\Dean\TimetableController as DeanTimetableController;
use App\Http\Controllers\Gec\DashboardController as GecDashboardController;
use App\Http\Controllers\Gec\InstructorController as GecInstructorController;
use App\Http\Controllers\Gec\InstructorUnitController as GecInstructorUnitController;
use App\Http\Controllers\Gec\PrintController as GecPrintController;
use App\Http\Controllers\Gec\ScheduleArchiveController as GecScheduleArchiveController;
use App\Http\Controllers\Gec\ScheduleController as GecScheduleController;
use App\Http\Controllers\Gec\SettingsController as GecSettingsController;
use App\Http\Controllers\Gec\SubjectAssignmentController as GecSubjectAssignmentController;
use App\Http\Controllers\Gec\SubjectController as GecSubjectController;
use App\Http\Controllers\Gec\TimetableController as GecTimetableController;
use App\Http\Controllers\Instructor\DashboardController as InstructorDashboardController;
use App\Http\Controllers\Instructor\PrintController as InstructorPrintController;
use App\Http\Controllers\Instructor\ProfileController as InstructorProfileController;
use App\Http\Controllers\Instructor\RoomScannerController as InstructorRoomScannerController;
use App\Http\Controllers\Instructor\WorkloadController as InstructorWorkloadController;
use App\Http\Controllers\PortalNotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\PrintController as StudentPrintController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\RoomScannerController as StudentRoomScannerController;
use App\Http\Controllers\Student\StudyLoadController as StudentStudyLoadController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

Route::get('/', function (): View {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function (): View|RedirectResponse {
    /** @var User $user */
    $user = Auth::user();

    if ($user->getAttribute('role') === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    if ($user->getAttribute('role') === 'dean') {
        return redirect()->route('dean.dashboard');
    }

    if ($user->getAttribute('role') === 'gec') {
        return redirect()->route('gec.dashboard');
    }

    if ($user->getAttribute('role') === 'instructor') {
        return redirect()->route('instructor.dashboard');
    }

    if ($user->getAttribute('role') === 'student') {
        return redirect()->route('student.dashboard');
    }

    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::patch('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');

        Route::get('/ms365-accounts', [Ms365StudentAccountController::class, 'index'])->name('ms365-accounts.index');
        Route::post('/ms365-accounts/import', [Ms365StudentAccountController::class, 'import'])->name('ms365-accounts.import');

        Route::get('/deleted-accounts', [UserController::class, 'deleted'])
            ->name('users.deleted');

        Route::patch('/users/{user}/restore', [UserController::class, 'restore'])
            ->whereNumber('user')
            ->name('users.restore');

        Route::resource('users', UserController::class)
            ->except(['show']);
    });

Route::middleware('gec')
    ->prefix('gec')
    ->name('gec.')
    ->group(function () {
        Route::get('/dashboard', [GecDashboardController::class, 'index'])
            ->name('dashboard');

        Route::patch('/profile', [ProfileController::class, 'update'])
            ->name('profile.update');

        Route::patch('/settings/semesters', [GecSettingsController::class, 'updateSemesters'])->name('settings.semesters');

        Route::get('/instructors', [GecInstructorController::class, 'index'])->name('instructors.index');
        Route::get('/instructors/create', [GecInstructorController::class, 'create'])->name('instructors.create');
        Route::post('/instructors', [GecInstructorController::class, 'store'])->name('instructors.store');
        Route::post('/instructors/import', [GecInstructorController::class, 'import'])->name('instructors.import');
        Route::get('/instructors/import-template', [GecInstructorController::class, 'importTemplate'])->name('instructors.import-template');
        Route::get('/instructors/{instructor}/edit', [GecInstructorController::class, 'edit'])->name('instructors.edit');
        Route::patch('/instructors/{instructor}', [GecInstructorController::class, 'update'])->name('instructors.update');
        Route::patch('/instructors/{instructor}/approve', [GecInstructorController::class, 'approve'])->name('instructors.approve');
        Route::delete('/instructors/{instructor}', [GecInstructorController::class, 'destroy'])->name('instructors.destroy');
        Route::get('/instructor-units', [GecInstructorUnitController::class, 'index'])->name('instructor-units.index');
        Route::patch('/instructor-units/defaults', [GecInstructorUnitController::class, 'updateDefaults'])->name('instructor-units.defaults');
        Route::patch('/instructor-units/{instructor}', [GecInstructorUnitController::class, 'update'])->name('instructor-units.update');
        Route::delete('/instructor-units/{instructor}', [GecInstructorUnitController::class, 'destroy'])->name('instructor-units.destroy');
        Route::resource('subjects', GecSubjectController::class)->except(['show']);
        Route::get('/subject-assignments', [GecSubjectAssignmentController::class, 'index'])->name('subject-assignments.index');
        Route::get('/subject-assignments/create', [GecSubjectAssignmentController::class, 'create'])->name('subject-assignments.create');
        Route::post('/subject-assignments', [GecSubjectAssignmentController::class, 'store'])->name('subject-assignments.store');
        Route::delete('/subject-assignments/remove-all', [GecSubjectAssignmentController::class, 'destroyAll'])->name('subject-assignments.destroy-all');
        Route::delete('/subject-assignments/{subject}', [GecSubjectAssignmentController::class, 'destroy'])->name('subject-assignments.destroy');
        Route::get('/create-schedule', [GecScheduleController::class, 'create'])->name('schedules.create');
        Route::post('/create-schedule', [GecScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('/timetable/delete-all', [GecTimetableController::class, 'destroyAll'])->name('timetable.destroy-all');
        Route::delete('/timetable/sections/{section}', [GecTimetableController::class, 'destroySection'])->name('timetable.sections.destroy');
        Route::resource('timetable', GecTimetableController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::get('/archive', [GecScheduleArchiveController::class, 'index'])->name('archive.index');
        Route::delete('/archive/sections/{section}', [GecScheduleArchiveController::class, 'destroySection'])->name('archive.sections.destroy');
        Route::patch('/archive/{schedule}/restore', [GecScheduleArchiveController::class, 'restore'])->name('archive.restore');
        Route::delete('/archive/{schedule}', [GecScheduleArchiveController::class, 'destroy'])->name('archive.destroy');
        Route::get('/print', [GecPrintController::class, 'index'])->name('print.index');
        Route::get('/print/instructor-workload/excel', [GecPrintController::class, 'instructorWorkloadExcel'])->name('print.instructor-workload.excel');
        Route::get('/print/{type}', [GecPrintController::class, 'report'])->name('print.report');
    });

Route::middleware('dean')
    ->prefix('dean')
    ->name('dean.')
    ->group(function () {
        Route::get('/dashboard', [DeanDashboardController::class, 'index'])->name('dashboard');
        Route::patch('/settings/semesters', [DeanSettingsController::class, 'updateSemesters'])->name('settings.semesters');
        Route::get('/instructors', [DeanInstructorController::class, 'index'])->name('instructors.index');
        Route::get('/instructors/create', [DeanInstructorController::class, 'create'])->name('instructors.create');
        Route::post('/instructors', [DeanInstructorController::class, 'store'])->name('instructors.store');
        Route::post('/instructors/import', [DeanInstructorController::class, 'import'])->name('instructors.import');
        Route::get('/instructors/import-template', [DeanInstructorController::class, 'importTemplate'])->name('instructors.import-template');
        Route::get('/instructors/{instructor}/edit', [DeanInstructorController::class, 'edit'])->name('instructors.edit');
        Route::patch('/instructors/{instructor}', [DeanInstructorController::class, 'update'])->name('instructors.update');
        Route::patch('/instructors/{instructor}/approve', [DeanInstructorController::class, 'approve'])->name('instructors.approve');
        Route::delete('/instructors/{instructor}', [DeanInstructorController::class, 'destroy'])->name('instructors.destroy');
        Route::get('/instructor-units', [DeanInstructorUnitController::class, 'index'])->name('instructor-units.index');
        Route::patch('/instructor-units/defaults', [DeanInstructorUnitController::class, 'updateDefaults'])->name('instructor-units.defaults');
        Route::patch('/instructor-units/{instructor}', [DeanInstructorUnitController::class, 'update'])->name('instructor-units.update');
        Route::delete('/instructor-units/{instructor}', [DeanInstructorUnitController::class, 'destroy'])->name('instructor-units.destroy');
        Route::get('/students', [DeanStudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [DeanStudentController::class, 'create'])->name('students.create');
        Route::post('/students', [DeanStudentController::class, 'store'])->name('students.store');
        Route::post('/students/import', [DeanStudentController::class, 'import'])->name('students.import');
        Route::get('/students/import-template', [DeanStudentController::class, 'importTemplate'])->name('students.import-template');
        Route::get('/students/{student}/edit', [DeanStudentController::class, 'edit'])->name('students.edit');
        Route::patch('/students/{student}', [DeanStudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [DeanStudentController::class, 'destroy'])->name('students.destroy');
        Route::post('/sections/import', [DeanSectionController::class, 'import'])->name('sections.import');
        Route::get('/sections/import-template', [DeanSectionController::class, 'importTemplate'])->name('sections.import-template');
        Route::resource('sections', DeanSectionController::class)->except(['show']);
        Route::get('/subject-assignments', [DeanSubjectAssignmentController::class, 'index'])->name('subject-assignments.index');
        Route::get('/subject-assignments/create', [DeanSubjectAssignmentController::class, 'create'])->name('subject-assignments.create');
        Route::post('/subject-assignments', [DeanSubjectAssignmentController::class, 'store'])->name('subject-assignments.store');
        Route::delete('/subject-assignments/remove-all', [DeanSubjectAssignmentController::class, 'destroyAll'])->name('subject-assignments.destroy-all');
        Route::delete('/subject-assignments/{subject}', [DeanSubjectAssignmentController::class, 'destroy'])->name('subject-assignments.destroy');
        Route::post('/subjects/import', [DeanSubjectController::class, 'import'])->name('subjects.import');
        Route::get('/subjects/import-template', [DeanSubjectController::class, 'importTemplate'])->name('subjects.import-template');
        Route::resource('subjects', DeanSubjectController::class)->except(['show']);
        Route::post('/rooms/import', [DeanRoomController::class, 'import'])->name('rooms.import');
        Route::get('/rooms/import-template', [DeanRoomController::class, 'importTemplate'])->name('rooms.import-template');
        Route::resource('rooms', DeanRoomController::class)->except(['show']);
        Route::get('/create-schedule', [DeanScheduleController::class, 'create'])->name('schedules.create');
        Route::post('/create-schedule', [DeanScheduleController::class, 'store'])->name('schedules.store');
        Route::delete('/timetable/delete-all', [DeanTimetableController::class, 'destroyAll'])->name('timetable.destroy-all');
        Route::delete('/timetable/sections/{section}', [DeanTimetableController::class, 'destroySection'])->name('timetable.sections.destroy');
        Route::resource('timetable', DeanTimetableController::class)->only(['index', 'edit', 'update', 'destroy']);
        Route::get('/archive', [DeanScheduleArchiveController::class, 'index'])->name('archive.index');
        Route::delete('/archive/sections/{section}', [DeanScheduleArchiveController::class, 'destroySection'])->name('archive.sections.destroy');
        Route::patch('/archive/{schedule}/restore', [DeanScheduleArchiveController::class, 'restore'])->name('archive.restore');
        Route::delete('/archive/{schedule}', [DeanScheduleArchiveController::class, 'destroy'])->name('archive.destroy');
        Route::get('/print', [DeanPrintController::class, 'index'])->name('print.index');
        Route::get('/print/instructor-workload/excel', [DeanPrintController::class, 'instructorWorkloadExcel'])->name('print.instructor-workload.excel');
        Route::get('/print/{type}', [DeanPrintController::class, 'report'])->name('print.report');
    });

Route::middleware('instructor')
    ->prefix('instructor')
    ->name('instructor.')
    ->group(function () {
        Route::get('/dashboard', [InstructorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/workload', [InstructorWorkloadController::class, 'index'])->name('workload.index');
        Route::get('/scanner', [InstructorRoomScannerController::class, 'index'])->name('scanner.index');
        Route::get('/scanner/rooms/{room}', [InstructorRoomScannerController::class, 'status'])->name('scanner.status');
        Route::get('/profile', [InstructorProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [InstructorProfileController::class, 'update'])->name('profile.update');
        Route::get('/print/workload', [InstructorPrintController::class, 'workload'])->name('print.workload');
        Route::get('/notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [PortalNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [PortalNotificationController::class, 'read'])->name('notifications.read');
    });

Route::middleware('student')
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/study-load', [StudentStudyLoadController::class, 'index'])->name('study-load.index');
        Route::get('/scanner', [StudentRoomScannerController::class, 'index'])->name('scanner.index');
        Route::get('/scanner/rooms/{room}', [StudentRoomScannerController::class, 'status'])->name('scanner.status');
        Route::get('/profile', [StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [StudentProfileController::class, 'update'])->name('profile.update');
        Route::get('/print/study-load', [StudentPrintController::class, 'studyLoad'])->name('print.study-load');
        Route::get('/notifications', [PortalNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [PortalNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [PortalNotificationController::class, 'read'])->name('notifications.read');
    });

require __DIR__.'/auth.php';
