<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class UserProductConsumerController extends Controller
{
    /**
     * Membuat pesanan baru dengan validasi dari UserService dan ProductService
     * CONSUMER: Endpoint ini mengambil data dari UserService dan ProductService
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);
        
        try {
            // Validasi user dari UserService
            $userServiceUrl = Config::get('services.user_service.url');
            $userResponse = Http::get($userServiceUrl . '/api/users/' . $request->user_id);
            
            if (!$userResponse->successful()) {
                return response()->json([
                    'error' => 'User validation failed',
                    'details' => $userResponse->json()
                ], $userResponse->status());
            }
            
            // Validasi produk dari ProductService
            $productServiceUrl = Config::get('services.product_service.url');
            $productResponse = Http::get($productServiceUrl . '/api/products/' . $request->product_id);
            
            if (!$productResponse->successful()) {
                return response()->json([
                    'error' => 'Product validation failed',
                    'details' => $productResponse->json()
                ], $productResponse->status());
            }
            
            // Validasi stok produk
            $product = $productResponse->json();
            if ($product['stock'] < $request->quantity) {
                return response()->json([
                    'error' => 'Insufficient stock',
                    'available' => $product['stock'],
                    'requested' => $request->quantity
                ], 400);
            }
            
            // Buat pesanan baru
            $order = Order::create([
                'user_id' => $request->user_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'status' => 'pending'
            ]);
            
            // Notifikasi ke RecommendationController
            $this->notifyRecommendationService($order);
            
            return response()->json($order, 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error creating order',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mendapatkan detail order dengan info user dan produk
     * CONSUMER: Endpoint ini mengambil data dari UserService dan ProductService
     */
    public function getOrderWithDetails($orderId)
    {
        try {
            $order = Order::find($orderId);
            
            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }
            
            // Ambil detail user dari UserService
            $userServiceUrl = Config::get('services.user_service.url');
            $userResponse = Http::get($userServiceUrl . '/api/users/' . $order->user_id);
            
            // Ambil detail produk dari ProductService
            $productServiceUrl = Config::get('services.product_service.url');
            $productResponse = Http::get($productServiceUrl . '/api/products/' . $order->product_id);
            
            // Gabungkan data
            $orderDetails = [
                'order' => $order,
                'user' => $userResponse->successful() ? $userResponse->json() : null,
                'product' => $productResponse->successful() ? $productResponse->json() : null
            ];
            
            return response()->json($orderDetails);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error fetching order details',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Metode untuk notifikasi ke RecommendationController saat order baru dibuat
     */
    public function notifyRecommendationService($order)
    {
        try {
            // URL ProductService dari config
            $productServiceUrl = Config::get('services.product_service.url');
            
            // Kirim notifikasi ke RecommendationController
            $response = Http::post($productServiceUrl . '/api/recommendations/update-history', [
                'user_id' => $order->user_id,
                'product_id' => $order->product_id,
            ]);
            
            return $response->successful();
        } catch (\Exception $e) {
            // Log error
            Log::error('Failed to notify recommendation service: ' . $e->getMessage());
            return false;
        }
    }
}