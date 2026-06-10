<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! $idempotencyKey) {
            return response()->json(['message' => 'Idempotency-Key header is required'], 400);
        }

        $requestBodyHash = hash('sha256', json_encode($request->all()));

        // Polling loop for race conditions
        $maxWaitSeconds = 10;
        $waited = 0;

        while ($waited < $maxWaitSeconds) {
            $record = \App\Models\IdempotencyRecord::where('idempotency_key', $idempotencyKey)->first();

            if ($record && $record->expires_at && $record->expires_at->isPast()) {
                $record->delete();
                $record = null; // Treat as if it was not found
            }

            if (! $record) {
                // Scenario A: Not found - insert and process
                $record = \App\Models\IdempotencyRecord::create([
                    'idempotency_key' => $idempotencyKey,
                    'request_body_hash' => $requestBodyHash,
                    'status' => 'processing',
                    'expires_at' => now()->addHours(24),
                ]);

                // Allow request to pass to the controller
                $response = $next($request);

                // Save the response
                $record->update([
                    'response_body' => json_decode($response->getContent(), true) ?? $response->getContent(),
                    'response_status' => $response->getStatusCode(),
                    'status' => 'complete',
                ]);

                return $response;
            }

            // Record exists. Check if hash matches.
            if ($record->request_body_hash !== $requestBodyHash) {
                // Scenario B: Found, hash mismatch
                return response()->json([
                    'message' => 'Idempotency key already used for a different request body.'
                ], 422);
            }

            if ($record->status === 'complete') {
                // Scenario C: Found, completed - return cached response
                return response()->json($record->response_body, $record->response_status)
                    ->header('X-Cache-Hit', 'true');
            }

            // Scenario D: Found, processing - poll/wait
            sleep(1);
            $waited++;
        }

        // If we waited 10 seconds and it's still processing, return a conflict.
        return response()->json(['message' => 'Request timeout while waiting for previous request to finish.'], 409);
    }
}
