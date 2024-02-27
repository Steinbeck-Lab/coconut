<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="COCONUT"
 * )
 */
class LoginController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/auth/login",
     * summary="Sign in",
     * description="Login by email and password",
     * operationId="authLogin",
     * tags={"auth"},
     *
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user credentials",
     *
     *    @OA\JsonContent(
     *       required={"email","password"},
     *
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *    ),
     * ),
     *
     * @OA\Response(
     *    response=200,
     *    description="Successful Operation",
     *
     *    @OA\JsonContent(
     *
     *       @OA\Property(property="access_token", type="string", example="4|2Y40Nmo5bGSlEeluQv7wYIKtG3OLw91cjU7Gx4F323"),
     *       @OA\Property(property="token_type", type="string", example="Bearer")
     *        )
     *    ),
     *
     * @OA\Response(
     *    response=401,
     *    description="Wrong Credentials Response",
     *
     *    @OA\JsonContent(
     *
     *       @OA\Property(property="message", type="string", example="Invalid login details")
     *        )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details',
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        if (! $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Account is not yet verified. Please verify your email address by clicking on the link we just emailed to you.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     *  @OA\Get(
     *      path="/api/auth/logout",
     *      summary="Sign out",
     *      tags={"auth"},
     *      security={{"sanctum":{}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="successful operation"
     *      ),
     *  )
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'logout' => 'Successful',
        ]);
    }
}
