<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\IssueTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /** POST /api/tokens – email + password + device_name → Bearer token (shown once). */
    public function store(IssueTokenRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            Log::notice('API token request failed', ['ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Store the expiry on the token too, so the client sees when it ends (config: 7 days).
        $minutes = config('sanctum.expiration');
        $token = $user->createToken(
            $request->validated('device_name'),
            ['jobs:read', 'jobs:write'],
            $minutes ? now()->addMinutes((int) $minutes) : null,
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
        ], 201);
    }

    /** DELETE /api/tokens/current – revoke the token used for this request. */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
