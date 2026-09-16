<?php

namespace App\Services;

use App\Models\CrossDepartmentInstructorRequest;
use App\Models\User;
use App\Notifications\CrossDepartmentInstructorRequestNotification;
use Illuminate\Support\Facades\Notification;

class CrossDepartmentInstructorRequestNotifier
{
    public function notifyReceivingDeans(CrossDepartmentInstructorRequest $instructorRequest): int
    {
        $recipients = User::query()
            ->where('course', $instructorRequest->requested_department)
            ->where('role', 'dean')
            ->where('account_status', 'active')
            ->get();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $instructorRequest->loadMissing('subject');
        Notification::send($recipients, new CrossDepartmentInstructorRequestNotification($instructorRequest));

        return $recipients->count();
    }

    public function notifyPendingRequestsForDean(User $dean): int
    {
        if ($dean->role !== 'dean' || $dean->account_status !== 'active' || blank($dean->course)) {
            return 0;
        }

        $requests = CrossDepartmentInstructorRequest::query()
            ->with('subject')
            ->where('requested_department', strtoupper((string) $dean->course))
            ->where('status', 'pending')
            ->get();

        foreach ($requests as $instructorRequest) {
            $dean->notify(new CrossDepartmentInstructorRequestNotification($instructorRequest));
        }

        return $requests->count();
    }
}
