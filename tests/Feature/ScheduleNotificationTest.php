<?php

namespace Tests\Feature;

use App\Models\AcademicSection;
use App\Models\ClassSchedule;
use App\Models\Room;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_schedule_edit_notifies_affected_instructors_and_students(): void
    {
        [$dean, $originalInstructor, $newInstructor, $student, $schedule, $newRoom] = $this->fixtures();

        $this->actingAs($dean)->put(route('dean.timetable.update', $schedule), [
            'instructor_id' => $newInstructor->id,
            'room_id' => $newRoom->id,
            'day' => 'T - Th',
            'start_time' => '09:30',
            'end_time' => '12:00',
        ])->assertRedirect(route('dean.timetable.index'))
            ->assertSessionHas('success');

        foreach ([$originalInstructor, $newInstructor, $student] as $recipient) {
            $notification = $recipient->fresh()->unreadNotifications()->first();

            $this->assertNotNull($notification);
            $this->assertSame('Class schedule updated', $notification->data['title']);
            $this->assertStringContainsString('IT201 for 2 - East', $notification->data['message']);
            $this->assertStringContainsString('T - Th', $notification->data['message']);
            $this->assertStringContainsString('Room 202', $notification->data['message']);
        }
    }

    public function test_instructor_and_student_topbars_show_live_notification_controls(): void
    {
        [$dean, $originalInstructor, , $student, $schedule, $newRoom] = $this->fixtures();

        $this->actingAs($dean)->put(route('dean.timetable.update', $schedule), [
            'instructor_id' => $originalInstructor->id,
            'room_id' => $newRoom->id,
            'day' => 'M - W',
            'start_time' => '09:30',
            'end_time' => '12:00',
        ])->assertRedirect();

        $this->actingAs($originalInstructor)->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('data-schedule-notifications', false)
            ->assertSee('z-index:900', false)
            ->assertSee('setCount(data.unread_count);', false)
            ->assertSee('Schedule notifications')
            ->assertSee('Class schedule updated')
            ->assertSeeInOrder(['data-schedule-notifications', 'class="profile"'], false);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-schedule-notifications', false)
            ->assertSee('overflow:visible', false)
            ->assertSee('Schedule notifications')
            ->assertSee('Class schedule updated')
            ->assertSeeInOrder(['data-schedule-notifications', 'class="profile"'], false);
    }

    public function test_portal_notification_feed_and_read_actions_are_user_scoped(): void
    {
        [$dean, $instructor, $otherInstructor, $student, $schedule, $newRoom] = $this->fixtures();

        $this->actingAs($dean)->put(route('dean.timetable.update', $schedule), [
            'instructor_id' => $instructor->id,
            'room_id' => $newRoom->id,
            'day' => 'M - W',
            'start_time' => '09:30',
            'end_time' => '12:00',
        ])->assertRedirect();

        $notification = $instructor->fresh()->unreadNotifications()->firstOrFail();

        $this->actingAs($instructor)->getJson(route('instructor.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.id', $notification->id)
            ->assertJsonPath('notifications.0.unread', true);

        $this->actingAs($otherInstructor)->postJson(route('instructor.notifications.read', $notification->id))
            ->assertNotFound();

        $this->actingAs($instructor)->postJson(route('instructor.notifications.read', $notification->id))
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('redirect_url', route('instructor.workload.index'));

        $this->assertNotNull($notification->fresh()->read_at);

        $studentNotification = $student->fresh()->unreadNotifications()->firstOrFail();
        $this->actingAs($student)->postJson(route('student.notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);
        $this->assertNotNull($studentNotification->fresh()->read_at);
    }

    /** @return array{User, User, User, User, ClassSchedule, Room} */
    private function fixtures(): array
    {
        $dean = User::factory()->create([
            'role' => 'dean',
            'course' => 'BSIT',
            'account_status' => 'active',
        ]);
        $originalInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => null,
            'account_status' => 'active',
        ]);
        $newInstructor = User::factory()->create([
            'role' => 'instructor',
            'course' => 'BSIT',
            'employment_type' => null,
            'account_status' => 'active',
        ]);
        $section = AcademicSection::create([
            'course' => 'BSIT',
            'name' => '2 - East',
            'year_level' => 2,
            'academic_year' => '2026-2027',
            'semester' => 'All',
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'course' => 'BSIT',
            'year_level' => 2,
            'academic_section_id' => $section->id,
            'account_status' => 'active',
        ]);
        $subject = Subject::create([
            'course' => 'BSIT',
            'code' => 'IT201',
            'name' => 'Data Structures',
            'subject_type' => 'Lecture',
            'classification' => 'Major',
            'year_level' => 2,
            'semester' => '1st',
            'units' => 3,
        ]);
        $room = Room::create([
            'course' => 'BSIT',
            'name' => 'Room 201',
            'room_type' => 'Laboratory',
        ]);
        $newRoom = Room::create([
            'course' => 'BSIT',
            'name' => 'Room 202',
            'room_type' => 'Laboratory',
        ]);
        $schedule = ClassSchedule::create([
            'course' => 'BSIT',
            'section_id' => $section->id,
            'subject_id' => $subject->id,
            'instructor_id' => $originalInstructor->id,
            'room_id' => $room->id,
            'academic_year' => '2026-2027',
            'semester' => '1st',
            'day' => 'M - W',
            'start_time' => '07:30',
            'end_time' => '10:00',
        ]);

        return [$dean, $originalInstructor, $newInstructor, $student, $schedule, $newRoom];
    }
}
