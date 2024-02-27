<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     *  @OA\Get(
     *      path="/api/auth/user/info",
     *      summary="User info",
     *      tags={"auth"},
     *      security={{"sanctum":{}}},
     *
     *      @OA\Response(
     *          response=200,
     *          description="successful operation"
     *      ),
     *  )
     */
    public function info(Request $request)
    {
        return $request->user();
    }
}
