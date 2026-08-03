<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OtherDepartmentInstructorSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['BSBA', 'BSHM', 'BSED', 'BEED'];

        foreach ($departments as $department) {
            for ($number = 1; $number <= 10; $number++) {
                $sequence = str_pad((string) $number, 2, '0', STR_PAD_LEFT);
                $email = "instructor{$sequence}@".strtolower($department).'.scheduler.test';
                $employmentType = $department === 'BSED' && in_array($number, [1, 3, 5, 10], true)
                    ? 'full_time'
                    : null;
                $accountDetails = [
                    'first_name' => $department,
                    'middle_name' => 'Instructor',
                    'last_name' => $sequence,
                    'role' => 'instructor',
                    'course' => $department,
                    'employment_type' => $employmentType,
                    'outside_work_end_time' => null,
                    'account_status' => 'active',
                ];

                $instructor = User::firstOrCreate(
                    ['email' => $email],
                    [...$accountDetails, 'password' => Hash::make('Instructor123!')],
                );

                // Keep seeded profile details current without resetting a
                // password that the instructor may already have changed.
                $instructor->update($accountDetails);
            }
        }
    }
}
