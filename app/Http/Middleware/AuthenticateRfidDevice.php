<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateRfidDevice
{
    /**
     * Ensure the Sanctum token is authorized for this device and endpoint.
     *
     * The token name is bound to the device UID (for example,
     * rfid-device:ESP32-IT-01) so a valid token cannot be reused while
     * impersonating another checkpoint reader.
     */
    public function handle(Request $request, Closure $next, string $ability, string $tokenPrefix = 'rfid-device'): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token || ! $token->can($ability)) {
            return response()->json([
                'message' => 'This RFID device is not authorized for this API action.',
            ], 403);
        }

        $deviceUid = $this->deviceUidFrom($request);

        if ($deviceUid === null) {
            return response()->json([
                'message' => 'The device_uid is required for authenticated RFID requests.',
            ], 422);
        }

        $expectedTokenName = $tokenPrefix.':'.$deviceUid;

        if (! hash_equals(strtoupper($expectedTokenName), strtoupper((string) $token->name))) {
            return response()->json([
                'message' => 'The API token does not belong to this RFID device.',
            ], 403);
        }

        return $next($request);
    }

    private function deviceUidFrom(Request $request): ?string
    {
        foreach ([
            'device_uid',
            'device',
            'reader_uid',
            'reader',
        ] as $key) {
            if ($request->filled($key)) {
                return Str::upper(trim((string) $request->input($key)));
            }
        }

        return null;
    }
}
