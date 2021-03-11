<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\PaymentLogs;
use Illuminate\Http\Request;


class WebhookController extends Controller
{
    public function midtransHandler(Request $request)
    {
        $data = $request->all();
        $signatureKey = $data['signature_key'];
        $grossAmount = $data['gross_amount'];
        $orerId = $data['order_id'];
        $serverKey = env('SERVER_KEY_MIDTRANS');
        $statusCode = $data['status_code'];
        $mySignatureKey = hash('sha512', $orerId . $statusCode . $grossAmount . $serverKey);
        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];
        if ($signatureKey != $mySignatureKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'invalid signature key'
            ], 400);
        }
        $realOrderId = explode('-', $data['order_id'])[0];
        $order = Orders::find($realOrderId);
        if (!$order) {
            return response()->json([
                'status' => 'success',
                'message' => 'order id is not found'
            ], 404);
        }
        if ($order->status === 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'operation not premitted'
            ], 400);
        }
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $order->status = 'challenge';
            } else if ($fraudStatus == 'accept') {
                $order->status = 'success';
            }
        } else if ($transactionStatus == 'settlement') {
            $order->status = 'success';
        } else if (
            $transactionStatus == 'cancel' ||
            $transactionStatus == 'deny' ||
            $transactionStatus == 'expire'
        ) {
            $order->status = 'failure';
        } else if ($transactionStatus == 'pending') {
            $order->status = 'pending';
        }
        $historyPayment = [
            'order_id' => $realOrderId,
            'status'   => $transactionStatus,
            'raw_response' => json_encode($data),
            'payment_type' => $type
        ];
        PaymentLogs::create($historyPayment);
        $order->save();
        if ($order->status === 'success') {
            createPremiumAccess(['user_id' => $order->user_id, 'course_id' => $order->course_id]);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'ok'
        ]);
    }
}
