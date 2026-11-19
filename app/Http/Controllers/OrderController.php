<?php

namespace App\Http\Controllers;

use App\Jobs\SendOrderConfirmation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();

        $result = [];
        foreach ($orders as $order) {
            $user = User::find($order->user_id);
            $itemsCount = OrderItem::where('order_id', $order->id)->count();

            $result[] = [
                'id' => $order->id,
                'amount' => $order->amount,
                'status' => $order->status,
                'customer' => $user->name,
                'items_count' => $itemsCount,
            ];
        }

        return response()->json($result, 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required',
        ]);

        try {
            $user = User::find($request->user_id);

            $order = Order::create($request->all());

            $total = 0;
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $total += $product->price * $item['quantity'];
                $order->items()->create($item);
            }

            if ($user->balance >= $total) {
                $user->balance = $user->balance - $total;
                $user->save();
                $order->status = 'paid';
            } else {
                $order->status = 'failed';
            }

            $order->amount = $total;
            $order->save();

            SendOrderConfirmation::dispatch($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 200);
        }

        return response()->json($order, 200);
    }

    public function cancel($id)
    {
        try {
            $order = Order::findOrFail($id);

            $order->user->increment('balance', $order->amount);
            $order->status = 'cancelled';
            $order->save();
        } catch (\Exception $e) {
            Log::error('Cancel failed: ' . $e->getMessage() . ' payload: ' . json_encode(request()->all()));
        }

        return response()->json(['message' => 'Order cancelled'], 200);
    }

    public function monthlyReport(Request $request)
    {
        if ($request->input('is_admin') != 'true') {
            return response()->json(['error' => 'Forbidden'], 200);
        }

        $month = $_GET['month'];

        $total = DB::select("SELECT SUM(amount) AS total FROM orders WHERE MONTH(created_at) = " . $month);

        return $total;
    }

    public function customersWithoutOrders()
    {
        $users = User::all();

        $result = [];
        foreach ($users as $user) {
            $ordersCount = Order::where('user_id', $user->id)->count();

            if ($ordersCount == 0) {
                $result[] = $user;
            }
        }

        return $result;
    }
}
