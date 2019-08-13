<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use App\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return View
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
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return redirect('admin/plan');
        //return view('admin.subscription.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
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
     * Display the specified resource.
     *
     * @param  \App\SubscriptionPlan  $subscriptionPlan
     * @return \Illuminate\Http\Response
     */
    public function show(SubscriptionPlan $subscriptionPlan)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\SubscriptionPlan  $subscriptionPlan
     * @return \Illuminate\Http\Response
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\SubscriptionPlan  $subscriptionPlan
     * @return \Illuminate\Http\Response
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
        /*$plan_name = isset($request->plan_name) ? $request->plan_name : '';
        $price = isset($request->price) ? $request->price : '';
        $credit = isset($request->credit) ? $request->credit : '';
        try {
            $subscriptionPlanObj = SubscriptionPlan::find($id);
            $subscriptionPlanObj->name = $plan_name;
            $subscriptionPlanObj->price = $price;
            $subscriptionPlanObj->credit = $credit;
            if($subscriptionPlanObj->save())
            {
                return redirect(route('admin.subscription.index'))->with('success', 'Subscription Plan Update Successfully');
            }
            session()->flash('error', 'Subscription Plan Can not Update Successfully');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
        }*/
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\SubscriptionPlan  $subscriptionPlan
     * @return \Illuminate\Http\Response
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

    public function ChangeStatus(Request $request)
    {
        try{
            $planObj = SubscriptionPlan::find($request->id);
            $planObj->status = $request->status;
            if($planObj->save()){
                session()->flash('success', 'Status Successfully Change');
                return 'true';
            }
            session()->flash('error', 'Status Change Fail Please Try Again');
            return 'false';
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return 'false';
        }
    }
}
