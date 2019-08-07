<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use App\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionsController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        try{
            $planListObj = SubscriptionPlan::all();
            if(!$planListObj->isEmpty()){
                return view('admin.subscription.index', compact('planListObj'));
            }
            return view('admin.subscription.index');
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return view('admin.subscription.index');
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
        try{
            $subscriptionPlanObj = New SubscriptionPlan();
            $subscriptionPlanObj->name = $plan_name;
            $subscriptionPlanObj->price = $price;
            $subscriptionPlanObj->credit = $credit;
            if($subscriptionPlanObj->save())
            {
                return redirect(route('admin.subscription.index'))->with('success', 'Subscription Plan Added Successfully');
            }
            session()->flash('error', 'Subscription Plan Can not Added Successfully');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return view('admin.subscription.create');
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
        $plan_name = isset($request->plan_name) ? $request->plan_name : '';
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
        }
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
                return redirect(route('admin.plan.index'))->with('success', 'Subscription Plan Delete Successfully');
            }
            session()->flash('error', 'Subscription Plan Can not Deleted Successfully');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
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
