<?php

use Illuminate\Database\Seeder;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Plan;
use Stripe\Error\Api;

class SubscriptionPlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws Api
     */
    public function run()
    {
        Stripe::setApiKey(config('settings.stripe.secret'));

        $defaultPlans = [
            ['name' => 'Pro', 'credits' => 70, 'price' => 70],
            ['name' => 'Plus', 'credits' => 40, 'price' => 40],
            ['name' => 'Basic', 'credits' => 10, 'price' => 10],
        ];

        try {
            $product = Product::retrieve(config('settings.stripe.product'));
        } catch (Exception $exception) {
            $product = Product::create([
                'id'        => config('settings.stripe.product'),
                'name'      => '80bots Credits',
                'type'      => 'service'
            ]);

            $plans = [];

            foreach ($defaultPlans as $plan) {
                $created = Plan::create([
                    'currency'  => 'usd',
                    'interval'  => 'month',
                    'product'   => $product->id,
                    'amount'    => $plan['price'] * 100,
                    'nickname'  => $plan['name'],
                    'metadata'  => [
                        'credits' => $plan['credits']
                    ]
                ]);
                array_push($plans, $created);
            }
        }

        $plans = Plan::all(['product' => $product->id]);

        foreach ($plans->data as $plan) {
            DB::table('subscription_plans')->insert([
                'name' => $plan->nickname,
                'price' => floor($plan->amount / 100),
                'credit' => $plan->metadata->credits,
                'stripe_plan' => $plan->id,
                'slug' => strtolower($plan->nickname)
            ]);
        }
    }
}
