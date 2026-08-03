<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BsitInstructorSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = [
            ['Dino', null, 'Ilustrisimo', 'full_time'],
            ['Kurt Bryan', null, 'Alegre', 'full_time'],
            ['Juniel', null, 'Marfa', 'full_time'],
            ['Alvine', null, 'Billones', 'full_time'],
            ['Jared', null, 'Cueva', 'full_time'],
            ['Richard', null, 'Bracero', 'full_time'],
            ['Danilo', null, 'Villarino', 'full_time'],
            ['Emily', null, 'Ilustrisimo', 'full_time'],
            ['Jessica', null, 'Alcazar', 'full_time'],
            ['Jose', null, 'Quiamco', 'full_time'],
            ['Glinford', null, 'Buncal', 'full_time'],
            ['Mary', 'Ann', 'Sisdoyro', 'full_time'],
            ['Sheena', null, 'Arda', 'full_time'],
            ['Cheska', null, 'Jumantoc', 'full_time'],
            ['Kent', null, 'Medallo', 'flexible_part_time'],
            ['Vicente', null, 'Layao', 'flexible_part_time'],
            ['Elmer', null, 'Lasala', 'flexible_part_time'],
            ['Vegeant', null, 'Santillan', 'flexible_part_time'],
            ['Jason', null, 'Gila', 'flexible_part_time'],
        ];

        foreach ($instructors as [$firstName, $middleName, $lastName, $employmentType]) {
            $emailFirstName = preg_replace('/[^a-z0-9]+/', '', strtolower($firstName));
            $emailLastName = preg_replace('/[^a-z0-9]+/', '', strtolower($lastName));
            $email = "{$emailFirstName}.{$emailLastName}@bsit.scheduler.test";
            $accountDetails = [
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'role' => 'instructor',
                'course' => 'BSIT',
                'employment_type' => $employmentType,
                'outside_work_end_time' => null,
                'account_status' => 'active',
            ];
            $instructor = User::firstOrCreate(
                ['email' => $email],
                [...$accountDetails, 'password' => Hash::make('Instructor123!')],
            );

            // Keep the seeded profile information current without resetting a
            // password that the instructor may already have changed.
            $instructor->update($accountDetails);
        }
    }
}
