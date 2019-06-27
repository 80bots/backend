<?php

use Illuminate\Database\Seeder;

class SubscriptionPlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('subscription_plans')->insert([
            'name' => 'Basic',
            'price' => 10,
            'credit' => 10,
            'stripe_plan' => 'plan_FKYx5zeMNetjVk',
            'slug' => 'basic'
        ]);
        DB::table('subscription_plans')->insert([
            'name' => 'Plus',
            'price' => 30,
            'credit' => 40,
            'stripe_plan' => 'plan_FKYwWZT9roi1n5',
            'slug' => 'plus'
        ]);
        DB::table('subscription_plans')->insert([
            'name' => 'Pro',
            'price' => 50,
            'credit' => 70,
            'stripe_plan' => 'plan_FKYy7DmM4NuqaI',
            'slug' => 'pro'
        ]);
    }
}
