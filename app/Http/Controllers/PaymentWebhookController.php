<?php

namespace App\Http\Controllers;

use App\Exceptions\Payment\AmountMismatchException;
use App\Exceptions\Payment\InvalidSignatureException;
use App\Exceptions\Payment\PaymentNotConfiguredException;
use App\Services\SettlementService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, SettlementService $settlement): JsonResponse
    {
        try {
            $trx = $settlement->handleNotification($request->all());
        } catch (PaymentNotConfiguredException $e) {
            return response()->json(['message' => $e->getMessage()], 503);
        } catch (InvalidSignatureException) {
            return response()->json(['message' => 'Invalid signature'], 403);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Unknown order'], 404);
        } catch (AmountMismatchException) {
            return response()->json(['message' => 'Amount mismatch'], 422);
        }

        return response()->json(['message' => 'OK', 'status' => $trx->status->value]);
    }
}
