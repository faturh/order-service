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
        Log::info('Received create order request', $request->all());
        
        $request->validate([
            'user_id' => 'required|integer',
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);
        
        try {
            // Validasi user dari UserService - gunakan hardcoded URL
            $userServiceUrl = 'http://localhost:8001';
            Log::info("Connecting to UserService: " . $userServiceUrl . '/api/users/' . $request->user_id);
            
            $userResponse = Http::get($userServiceUrl . '/api/users/' . $request->user_id);
            
            if (!$userResponse->successful()) {
                Log::error("User validation failed", [
                    'status' => $userResponse->status(),
                    'response' => $userResponse->body()
                ]);
                return response()->json([
                    'error' => 'User validation failed',
                    'details' => $userResponse->json()
                ], $userResponse->status());
            }
            
            // Validasi produk dari ProductService - gunakan hardcoded URL
            $productServiceUrl = 'http://localhost:8002';
            Log::info("Connecting to ProductService: " . $productServiceUrl . '/api/products/' . $request->product_id);
            
            $productResponse = Http::get($productServiceUrl . '/api/products/' . $request->product_id);
            
            if (!$productResponse->successful()) {
                Log::error("Product validation failed", [
                    'status' => $productResponse->status(),
                    'response' => $productResponse->body()
                ]);
                return response()->json([
                    'error' => 'Product validation failed',
                    'details' => $productResponse->json()
                ], $productResponse->status());
            }
            
            // Validasi stok produk
            $product = $productResponse->json();
            
            // Tambahan condition jika property stock tidak ada
            if (!isset($product['stock'])) {
                Log::warning("Product doesn't have stock property", ['product' => $product]);
                
                // Buat pesanan baru tanpa validasi stock
                $order = Order::create([
                    'user_id' => $request->user_id,
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'status' => 'pending'
                ]);
                
                Log::info("Order created successfully", ['order_id' => $order->id]);
                
                // Notifikasi ke RecommendationService
                $this->notifyRecommendationService($order);
                
                return response()->json($order, 201);
            }
            
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
            
            Log::info("Order created successfully", ['order_id' => $order->id]);
            
            // Notifikasi ke RecommendationController
            $this->notifyRecommendationService($order);
            
            return response()->json($order, 201);
            
        } catch (\Exception $e) {
            Log::error("Error creating order: " . $e->getMessage());
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
            
            // Ambil detail user dari UserService - gunakan hardcoded URL
            $userServiceUrl = 'http://localhost:8001';
            $userResponse = Http::get($userServiceUrl . '/api/users/' . $order->user_id);
            
            // Ambil detail produk dari ProductService - gunakan hardcoded URL
            $productServiceUrl = 'http://localhost:8002';
            $productResponse = Http::get($productServiceUrl . '/api/products/' . $order->product_id);
            
            // Gabungkan data
            $orderDetails = [
                'order' => $order,
                'user' => $userResponse->successful() ? $userResponse->json() : null,
                'product' => $productResponse->successful() ? $productResponse->json() : null
            ];
            
            return response()->json($orderDetails);
            
        } catch (\Exception $e) {
            Log::error("Error fetching order details: " . $e->getMessage());
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
            // Gunakan hardcoded URL
            $productServiceUrl = 'http://localhost:8002';
            
            Log::info("Notifying recommendation service", [
                'url' => $productServiceUrl . '/api/recommendations/update-history',
                'user_id' => $order->user_id,
                'product_id' => $order->product_id,
            ]);
            
            // Kirim notifikasi ke RecommendationController
            $response = Http::post($productServiceUrl . '/api/recommendations/update-history', [
                'user_id' => $order->user_id,
                'product_id' => $order->product_id,
            ]);
            
            if (!$response->successful()) {
                Log::warning("Recommendation service response was not successful", [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
            
            return $response->successful();
        } catch (\Exception $e) {
            // Log error
            Log::error('Failed to notify recommendation service: ' . $e->getMessage());
            return false;
        }
    }
}