<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        // Return all announcements with their linked office hour data, newest first
        return response()->json(Announcement::with('officeHour')->latest()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $data['author_name'] = 'Instructor'; // Using a mock author until you build out Auth completely

        $announcement = Announcement::create($data);
        return response()->json($announcement, 201);
    }
}