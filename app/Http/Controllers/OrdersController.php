<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use function PHPSTORM_META\map;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $order = Orders::query();
        $user_id = $request->query('user_id');
        $order->when($user_id, function ($query) use ($user_id) {
            return $query->where('user_id', $user_id);
        });
        return response()->json([
            'status' => 'success',
            'data'  => $order->get()
        ]);
    }
    public function store(Request $request)
    {
        $user = $request->input('user');
        $course = $request->input('course');

        $newOrder = Orders::create([
            'user_id' => $user['id'],
            'course_id' => $course['id']
        ]);
        $transactionDetails = [
            'order_id' => $newOrder->id . '-' . Str::random(5),
            'gross_amount' => $course['price']
        ];
        $itemDetails = [
            [
                "id" => $course['id'],
                "price" => $course['price'],
                "quantity" => 1,
                "name" => $course['name'],
                "brand" => 'Buld With Fajar',
                'category' => 'Online Course'
            ]
        ];
        $customerDetails = [
            'first_name' => $user['name'],
            'email' => $user['email']
        ];
        $midtransParams = [
            "transaction_details" => $transactionDetails,
            "item_details" => $itemDetails,
            "customer_details" => $customerDetails
        ];
        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);
        $newOrder->snap_url = $midtransSnapUrl;
        $newOrder->metadata = [
            [
                'course_id' => $course['id'],
                'course_price' => $course['price'],
                'course_name' => $course['name'],
                'course_thumnail' => $course['thumnail'],
                'course_level' => $course['level']
            ]
        ];
        $newOrder->save();
        return response()->json([
            'status' => 'success',
            'data'   => $newOrder
        ]);
    }
    private function getMidtransSnapUrl($parms)
    {
        \Midtrans\Config::$serverKey = env('SERVER_KEY_MIDTRANS');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_PRODUCTION');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_3DS');
        $snapUrl = \Midtrans\Snap::createTransaction($parms)->redirect_url;
        return $snapUrl;
    }
}
