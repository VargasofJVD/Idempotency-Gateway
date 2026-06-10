<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'currency' => 'required|string',
        ]);

        // Simulate processing delay
        sleep(2);

        return response()->json([
            'message' => "Charged {$validated['amount']} {$validated['currency']}",
            'transaction_id' => Str::uuid()->toString(),
        ], 201);
    }
}
