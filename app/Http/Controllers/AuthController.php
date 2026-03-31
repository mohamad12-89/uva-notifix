<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendVerificationCode;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $code = (string) random_int(100000, 999999);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'verification_code' => $code,
            'verification_code_sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new SendVerificationCode($user, $code));

        return response()->json(['message' => 'Registration successful. Please check your email for a verification code.'], 201);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'code' => 'required|string|digits:6',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified.'], 422);
        }

        if (is_null($user->verification_code) || is_null($user->verification_code_sent_at)) {
            return response()->json(['message' => 'Verification code not requested or already used.'], 422);
        }

        $codeSentAt = Carbon::parse($user->verification_code_sent_at);
        if ($codeSentAt->addMinutes(10)->isPast()) {
            // Optional: You could add logic here to resend the code
            return response()->json(['message' => 'Verification code has expired.'], 422);
        }

        if ($user->verification_code !== $data['code']) {
            return response()->json(['message' => 'Invalid verification code.'], 422);
        }

        $user->email_verified_at = now();
        $user->verification_code = null;
        $user->verification_code_sent_at = null;
        $user->save();

        // Similar to login, return user. In a real app, you'd likely return an API token here.
        return response()->json($user);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if (! $user->email_verified_at) {
            // Optional: You could add logic here to resend the code
            return response()->json(['message' => 'Please verify your email before logging in.'], 403);
        }

        return response()->json($user);
    }
}
