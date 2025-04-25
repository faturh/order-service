<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Reset database (hati-hati dengan foreign key)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('orders')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // Buat user dengan email yang berbeda
        $user = User::create([
            'name' => 'Order User',
            'email' => 'order_user@example.com',
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'email_verified_at' => now(),
        ]);
        
        // Buat beberapa pesanan
        Order::create([
            'user_id' => 1,
            'product_id' => 1,
            'quantity' => 2,
            'status' => 'pending'
        ]);
        
        Order::create([
            'user_id' => 1,
            'product_id' => 2,
            'quantity' => 1,
            'status' => 'completed'
        ]);
    }
}