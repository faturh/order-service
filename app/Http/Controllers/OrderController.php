<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar semua pesanan
     * PROVIDER: Endpoint ini digunakan oleh layanan lain
     */
    public function index()
    {
        $orders = Order::all();
        return response()->json($orders);
    }

    /**
     * Menampilkan detail pesanan berdasarkan ID
     * PROVIDER: Endpoint ini digunakan oleh layanan lain
     */
    public function show($id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        
        return response()->json($order);
    }
    
    /**
     * Menampilkan semua pesanan berdasarkan ID user
     * PROVIDER: Endpoint ini digunakan oleh UserService
     */
    public function getOrdersByUser($userId)
    {
        $orders = Order::where('user_id', $userId)->get();
        return response()->json($orders);
    }

    /**
     * Menyimpan pesanan baru
     * PROVIDER: Endpoint ini bisa digunakan oleh layanan lain
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'status' => 'required|string|in:pending,processing,completed,cancelled',
        ]);

        // UserConsumerController dan ProductConsumerController akan digunakan
        // untuk validasi user_id dan product_id sebelum menyimpan order

        $order = Order::create([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'status' => $request->status,
        ]);

        return response()->json($order, 201);
    }

    /**
     * Mengupdate data pesanan
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        
        $request->validate([
            'user_id' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'integer|min:1',
            'status' => 'string|in:pending,processing,completed,cancelled',
        ]);

        $order->update([
            'user_id' => $request->user_id ?? $order->user_id,
            'product_id' => $request->product_id ?? $order->product_id,
            'quantity' => $request->quantity ?? $order->quantity,
            'status' => $request->status ?? $order->status,
        ]);

        return response()->json($order);
    }

    /**
     * Menghapus pesanan
     */
    public function destroy($id)
    {
        $order = Order::find($id);
        
        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }
        
        $order->delete();
        
        return response()->json(['message' => 'Order deleted successfully']);
    }
}