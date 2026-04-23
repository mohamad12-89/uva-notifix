<?php

namespace App\Http\Controllers;

use App\Models\TaBio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaBioController extends Controller
{
    public function index()
    {
        return TaBio::query()
            ->orderBy('name')
            ->get()
            ->map(fn (TaBio $bio) => $this->withProfileImageUrl($bio));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'year' => 'required|string',
            'major' => 'required|string',
            'email' => 'required|string',
            'notes' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $existingBio = TaBio::where('email', $data['email'])->first();
        if ($existingBio) {
            return response()->json(['message' => 'You already have a TA bio.'], 403);
        }

        if ($request->hasFile('profile_image')) {
            $data['profile_image_path'] = $request->file('profile_image')->store('ta-bios', 'public');
        }

        $bio = TaBio::create($data);

        return $this->withProfileImageUrl($bio);
    }

    public function update(Request $request, TaBio $taBio)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'year' => 'required|string',
            'major' => 'required|string',
            'email' => 'required|string',
            'notes' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'clear_profile_image' => 'nullable|boolean',
        ]);

        $clearProfileImage = filter_var($request->input('clear_profile_image', false), FILTER_VALIDATE_BOOLEAN);
        if ($clearProfileImage && $taBio->profile_image_path) {
            Storage::disk('public')->delete($taBio->profile_image_path);
            $data['profile_image_path'] = null;
        }

        if ($request->hasFile('profile_image')) {
            if ($taBio->profile_image_path) {
                Storage::disk('public')->delete($taBio->profile_image_path);
            }
            $data['profile_image_path'] = $request->file('profile_image')->store('ta-bios', 'public');
        }

        $taBio->update($data);

        return $this->withProfileImageUrl($taBio->refresh());
    }

    public function destroy(TaBio $taBio)
    {
        if ($taBio->profile_image_path) {
            Storage::disk('public')->delete($taBio->profile_image_path);
        }

        $taBio->delete();

        return response()->json(['message' => 'TA bio deleted.']);
    }

    private function withProfileImageUrl(TaBio $bio): TaBio
    {
        $bio->profile_image_url = $bio->profile_image_path
            ? '/storage/'.$bio->profile_image_path
            : null;

        return $bio;
    }
}
