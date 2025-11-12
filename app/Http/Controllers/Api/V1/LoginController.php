<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     *
     * @param  LoginRequest  $request
     * @return Response
     * @throws ValidationException
     */
    public function store(LoginRequest $request): Response
    {
        $request->authenticate();

        $token = $request->user()->createToken('auth_token');

        return response([
            'token' => $token->plainTextToken,
        ]);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  Request  $request
     * @return Response
     */
    public function destroy(Request $request): Response
    {
        /** @var User */
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (!$token) {
            return response()->noContent(403);
        }

        if (is_a($token, \Laravel\Sanctum\PersonalAccessToken::class)) {
            $token->delete();
            $token->reFresh();
            $token->fresh();

            return response()->noContent(204);
        }

        return response()->noContent(422);
    }
}
