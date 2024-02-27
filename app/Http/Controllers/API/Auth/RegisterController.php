<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/auth/register",
     * summary="Register",
     * description="Register by providing details.",
     * operationId="authRegister",
     * tags={"auth"},
     *
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass registration details.",
     *
     *    @OA\JsonContent(
     *       required={"first_name","last_name","email","password","username"},
     *
     *       @OA\Property(property="first_name", type="string", format="first_name", example="Marie"),
     *       @OA\Property(property="last_name", type="string", format="last_name", example="Warren"),
     *       @OA\Property(property="email", type="string", format="email", example="marie.warren@email.com"),
     *       @OA\Property(property="username", type="string", format="username", example="marie123"),
     *       @OA\Property(property="orcid_id", type="string", format="orcid_id", example="0000-0003-2433-4341"),
     *       @OA\Property(property="password", type="string", format="password", example="secret1234"),
     *
     *    ),
     * ),
     *
     * @OA\Response(
     *    response=200,
     *    description="Successful Operation"
     *    ),
     * @OA\Response(
     *    response=422,
     *    description="Unprocessable Content"
     * )
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'username' => 'required|string',
        ]);

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'username' => $validatedData['username'],
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
