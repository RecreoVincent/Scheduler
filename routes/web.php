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
use App\Http\Controllers\Dean\SubjectAssignmentController as DeanSubjectAssignmentController;
use App\Http\Controllers\Dean\SubjectController as DeanSubjectController;
use App\Http\Controllers\Dean\TimetableController as DeanTimetableController;
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

Route::middleware('dean')
    ->prefix('dean')
    ->name('dean.')
    ->group(function () {
        Route::get('/dashboard', [DeanDashboardController::class, 'index'])->name('dashboard');
        Route::get('/instructors', [DeanInstructorController::class, 'index'])->name('instructors.index');
        Route::get('/instructor-units', [DeanInstructorUnitController::class, 'index'])->name('instructor-units.index');
        Route::patch('/instructor-units/{instructor}', [DeanInstructorUnitController::class, 'update'])->name('instructor-units.update');
        Route::patch('/instructors/{instructor}/approve', [DeanInstructorController::class, 'approve'])->name('instructors.approve');
        Route::delete('/instructors/{instructor}', [DeanInstructorController::class, 'destroy'])->name('instructors.destroy');
        Route::resource('sections', DeanSectionController::class)->except(['show']);
        Route::get('/subject-assignments', [DeanSubjectAssignmentController::class, 'index'])->name('subject-assignments.index');
        Route::get('/subject-assignments/create', [DeanSubjectAssignmentController::class, 'create'])->name('subject-assignments.create');
        Route::post('/subject-assignments', [DeanSubjectAssignmentController::class, 'store'])->name('subject-assignments.store');
        Route::delete('/subject-assignments/remove-all', [DeanSubjectAssignmentController::class, 'destroyAll'])->name('subject-assignments.destroy-all');
        Route::resource('subjects', DeanSubjectController::class)->except(['show']);
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
