<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\OfficeHour;
use App\Models\OfficeHourSignup;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OfficeHourController extends Controller
{
    public function index()
    {
        return OfficeHour::query()
            ->withCount('signups')
            ->orderBy('date')
            ->orderBy('time')
            ->get()
            ->map(function ($officeHour) {
                // Keep legacy field name for current frontend cards.
                $officeHour->attendance_count = $officeHour->signups_count;
                return $officeHour;
            });
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'ta_name' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'end_time' => 'required|after:time',
            'location' => 'required|string',
        ]);

        $data['attendance_count'] = 0;

        $officeHour = OfficeHour::create($data);

        $date = Carbon::parse($officeHour->date)->startOfDay();
        if ($date->greaterThanOrEqualTo(Carbon::today()) && $date->lessThanOrEqualTo(Carbon::today()->addDays(7))) {
            Announcement::create([
                'title' => "New Office Hour: {$officeHour->ta_name}",
                'body' => "A new office hour session has been scheduled.\n\nDetails:\nDate: {$officeHour->date}\nTime: {$officeHour->time} - {$officeHour->end_time}\nLocation: {$officeHour->location}",
                'author_name' => 'System Auto-Notice',
                'office_hour_id' => $officeHour->id,
            ]);
        }

        return $officeHour;
    }

    public function update(Request $request, OfficeHour $officeHour)
    {
        $data = $request->validate([
            'ta_name' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'end_time' => 'required|after:time',
            'location' => 'required|string',
        ]);

        $officeHour->update($data);

        if ($officeHour->wasChanged()) {
            Announcement::create([
                'title' => "Office Hour Updated: {$officeHour->ta_name}",
                'body' => "An office hour session has been updated.\n\nNew Details:\nDate: {$officeHour->date}\nTime: {$officeHour->time} - {$officeHour->end_time}\nLocation: {$officeHour->location}",
                'author_name' => 'System Auto-Notice',
                'office_hour_id' => $officeHour->id,
            ]);
        }

        return $officeHour->refresh();
    }

    public function destroy(OfficeHour $officeHour)
    {
        $taName = $officeHour->ta_name;
        $date = $officeHour->date;
        $time = $officeHour->time;
        $endTime = $officeHour->end_time;
        $location = $officeHour->location;

        $officeHour->delete();

        Announcement::create([
            'title' => "Office Hour Canceled: {$taName}",
            'body' => "An office hour session has been canceled.\n\nCanceled Session Details:\nDate: {$date}\nTime: {$time} - {$endTime}\nLocation: {$location}",
            'author_name' => 'System Auto-Notice',
        ]);

        return response()->json(['message' => 'Office hour deleted.']);
    }

    public function join(OfficeHour $officeHour)
    {
        $data = request()->validate([
            'student_name' => 'required|string|max:255',
            'student_email' => 'required|email',
        ]);

        if (!str_ends_with(strtolower($data['student_email']), '@virginia.edu')) {
            return response()->json(['message' => 'Student email must end with @virginia.edu'], 422);
        }

        OfficeHourSignup::firstOrCreate(
            [
                'office_hour_id' => $officeHour->id,
                'student_email' => strtolower($data['student_email']),
            ],
            [
                'student_name' => $data['student_name'],
            ],
        );

        $officeHour->attendance_count = $officeHour->signups()->count();
        $officeHour->save();

        return $officeHour->refresh();
    }

    public function unjoin(OfficeHour $officeHour)
    {
        $data = request()->validate([
            'student_email' => 'required|email',
        ]);

        $officeHour->signups()
            ->where('student_email', strtolower($data['student_email']))
            ->delete();

        $officeHour->attendance_count = $officeHour->signups()->count();
        $officeHour->save();

        return $officeHour->refresh();
    }

    public function signups(OfficeHour $officeHour)
    {
        $signups = $officeHour->signups()
            ->orderByDesc('created_at')
            ->get(['id', 'student_name', 'student_email', 'checked_in_at', 'created_at']);

        return response()->json([
            'office_hour_id' => $officeHour->id,
            'signups' => $signups,
            'signup_count' => $signups->count(),
            'checked_in_count' => $signups->whereNotNull('checked_in_at')->count(),
        ]);
    }

    public function checkIn(OfficeHour $officeHour, OfficeHourSignup $signup)
    {
        if ($signup->office_hour_id !== $officeHour->id) {
            return response()->json(['message' => 'Signup does not belong to this office hour.'], 422);
        }

        if (!$signup->checked_in_at) {
            $signup->checked_in_at = now();
            $signup->save();
        }

        return response()->json($signup->refresh());
    }

    public function analytics()
    {
        $officeHours = OfficeHour::all();
        $analytics = [];
        $id = 1;

        // Group by ISO week number using Collections (database-agnostic)
        $grouped = $officeHours->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('W');
        });

        foreach ($grouped as $weekNum => $weekItems) {
            $taGroups = $weekItems->groupBy('ta_name');
            foreach ($taGroups as $taName => $taItems) {
                $analytics[] = [
                    'id' => $id++,
                    'week' => 'Week ' . ltrim($weekNum, '0'),
                    'week_num' => $weekNum, // Kept temporarily for accurate sorting
                    'ta_name' => $taName,
                    'attendance' => $taItems->sum('attendance_count'),
                ];
            }
        }

        // Sort analytics by week descending, then attendance descending
        $analytics = collect($analytics)
            ->sortByDesc('attendance')
            ->sortByDesc('week_num')
            ->values()
            ->map(function ($item) {
                unset($item['week_num']); // Remove the temporary sorting key
                return $item;
            })->all();

        // Calculate real-time active sessions
        $now = Carbon::now();
        $activeSessions = OfficeHour::whereDate('date', $now->toDateString())
            ->whereTime('time', '<=', $now->toTimeString())
            ->whereTime('end_time', '>=', $now->toTimeString())
            ->count();

        $studentStats = OfficeHourSignup::query()
            ->selectRaw('student_email, MAX(student_name) as student_name')
            ->selectRaw('COUNT(*) as signed_up_count')
            ->selectRaw('SUM(CASE WHEN checked_in_at IS NOT NULL THEN 1 ELSE 0 END) as attended_count')
            ->selectRaw('MAX(created_at) as last_signup_at')
            ->groupBy('student_email')
            ->orderByDesc('last_signup_at')
            ->get();

        // Weekly join-time demand based on actual join actions (created_at), not check-ins.
        $weekdayMap = [
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
            7 => 'Sun',
        ];
        $weeklyJoinHeatmap = OfficeHourSignup::query()
            ->whereNotNull('created_at')
            ->get(['created_at'])
            ->map(function ($signup) use ($weekdayMap) {
                // Bucket joins in UVA local time so day/hour analytics match professor expectations.
                $dt = Carbon::parse($signup->created_at)->setTimezone('America/New_York');
                $weekdayNum = (int) $dt->isoWeekday();
                $hour24 = (int) $dt->format('G');
                $hourLabel = $dt->format('g A');
                return [
                    'bucket_key' => sprintf('%d-%02d', $weekdayNum, $hour24),
                    'weekday_num' => $weekdayNum,
                    'weekday' => $weekdayMap[$weekdayNum] ?? 'N/A',
                    'hour_24' => $hour24,
                    'hour_label' => $hourLabel,
                    'label' => sprintf('%s %s', $weekdayMap[$weekdayNum] ?? 'N/A', $hourLabel),
                ];
            })
            ->groupBy('bucket_key')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'label' => $first['label'],
                    'weekday_num' => $first['weekday_num'],
                    'hour_24' => $first['hour_24'],
                    'join_count' => $items->count(),
                ];
            })
            ->sortBy('weekday_num')
            ->sortBy('hour_24')
            ->values()
            ->all();

        $weeklyJoinPeaks = collect($weeklyJoinHeatmap)
            ->sortByDesc('join_count')
            ->values()
            ->take(12)
            ->values()
            ->all();

        return response()->json([
            'analytics' => $analytics,
            'activeSessions' => $activeSessions,
            'studentStats' => $studentStats,
            'weeklyJoinHeatmap' => $weeklyJoinHeatmap,
            'weeklyJoinPeaks' => $weeklyJoinPeaks,
        ]);
    }
}
