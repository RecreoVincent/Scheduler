<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $departments = ['BSIT', 'BSBA', 'BSHM', 'BSED', 'BEED', 'GEC'];
        $roles = ['dean', 'gec', 'instructor', 'student'];

        $statistics = [
            'total_users' => User::count(),
            'total_deans' => User::where('role', 'dean')->count(),
            'total_gec' => User::where('role', 'gec')->count(),
            'total_instructors' => User::where('role', 'instructor')->count(),
            'total_students' => User::where('role', 'student')->count(),
        ];

        $accountStatusSummary = function (?string $role = null): array {
            $query = User::query();

            if ($role !== null) {
                $query->where('role', $role);
            }

            $total = (clone $query)->count();
            $active = (clone $query)->where('account_status', 'active')->count();
            return [
                'total' => $total,
                'active' => $active,
                'active_percentage' => $total > 0 ? (int) round(($active / $total) * 100) : 0,
            ];
        };

        $accountStatusAnalytics = [
            'all' => $accountStatusSummary(),
            'dean' => $accountStatusSummary('dean'),
            'gec' => $accountStatusSummary('gec'),
            'instructor' => $accountStatusSummary('instructor'),
            'student' => $accountStatusSummary('student'),
        ];

        $analyticsByRole = [];

        foreach ($roles as $role) {
            $counts = array_fill_keys($departments, 0);
            $counts['Unassigned'] = 0;

            User::where('role', $role)
                ->pluck('course')
                ->each(function (?string $course) use (&$counts, $departments): void {
                    $department = strtoupper((string) $course);

                    if (in_array($department, $departments, true)) {
                        $counts[$department]++;
                    } else {
                        $counts['Unassigned']++;
                    }
                });

            $analyticsByRole[$role] = $counts;
        }

        $recentUsers = User::whereIn('role', [
            'dean',
            'gec',
            'instructor',
            'student',
        ])
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'statistics',
            'accountStatusAnalytics',
            'analyticsByRole',
            'recentUsers'
        ));
    }
}
