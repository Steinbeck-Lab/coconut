<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'username' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users',
            'affiliation' => 'required|string',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|min:8',
        ]);

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'username' => $validatedData['username'],
            'name' => $validatedData['first_name'].' '.$validatedData['last_name'],
            'orcid_id' => $request['orcid_id'],
            'affiliation' => $request['affiliation'],
            'password' => Hash::make($validatedData['password']),
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Successful created user. Please verify your email address by clicking on the link we just emailed to you.',
            'token' => $token,
        ],
            201);
    }
}
