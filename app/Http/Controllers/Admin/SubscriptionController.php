<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use App\SubscriptionPlan;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

class SubscriptionController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse|View
     */
    public function index()
    {
        try {
            $plans = SubscriptionPlan::all();
            return $this->success($plans->toArray());
        } catch (\Exception $exception){
            $this->error('System Error', $exception->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|RedirectResponse|Response|Redirector
     */
    public function create()
    {
        return redirect('admin/plan');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $plan_name = isset($request->plan_name) ? $request->plan_name : '';
        $price = isset($request->price) ? $request->price : '';
        $credit = isset($request->credit) ? $request->credit : '';
        try {
            $subscriptionPlanObj = New SubscriptionPlan();
            $subscriptionPlanObj->name = $plan_name;
            $subscriptionPlanObj->price = $price;
            $subscriptionPlanObj->credit = $credit;
            if($subscriptionPlanObj->save())
            {
                response(null, 200);
            } else {
                $this->error('System Error', 'Cannot create subscription');
            }
        } catch (\Exception $exception){
            $this->error('System Error', $exception->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return Application|Factory|RedirectResponse|Response|View
     */
    public function edit($id)
    {
        try{
            $plan = SubscriptionPlan::find($id);
            if(isset($plan) && !empty($plan)){
                return view('admin.subscription.edit',compact('plan', 'id'));
            }
            session()->flash('error', 'Please Try Again');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse|Response
     */
    public function update(Request $request, $id)
    {
        try {
            $updateData = $request->validate([
                'update.status' => 'in:active,inactive',
                'update.name'   => 'string',
                'update.credit' => 'integer',
                'update.price'  => 'integer'
            ]);

            $plan = SubscriptionPlan::find($id);

            foreach ($updateData['update'] as $key => $value) {
                switch ($key) {
                    case 'status':
                        $plan->status = $value;
                        break;
                    case 'name':
                        $plan->name = $value;
                        break;
                    case 'credit':
                        $plan->credit = $value;
                    case 'price':
                        $plan->price = $value;
                }
            }

            if ($plan->save()) {
                return $this->success($plan->toArray());
            }
            return $this->error('System Error', 'Cannot update user at this moment');
        } catch (\Exception $exception){
            return $this->error('System Error', $exception->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return JsonResponse|Response
     */
    public function destroy($id)
    {
        try{
            $subscription = SubscriptionPlan::find($id);
            if($subscription->delete()){
                return response(null, 200);
            } else {
                return $this->error('System Error', 'Cannot delete subscription plan right now');
            }
        } catch (\Exception $exception){
            return $this->error('System Error', $exception->getMessage());
        }
    }
}
