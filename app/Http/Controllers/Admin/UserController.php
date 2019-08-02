<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use App\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Exception;
use Illuminate\Support\Facades\Auth;

class UserController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        try{
            $userListObj = User::where('role_id', 2)->get();
            if(!$userListObj->isEmpty()){
                return view('admin.user.index',compact('userListObj'));
            } else {
                session()->flash('error', 'User Not Found');
                return view('admin.user.index');
            }
        } catch (Exception $exception){
            session()->flash('error', $exception->getMessage());
            return view('admin.user.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function changeStatus(Request $request){
        try{
            $userObj = User::find($request->id);
            $userObj->status = $request->status;
            $userObj->verification_token = '';
            if($userObj->save()){
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

    public function updateCredit(Request $request){
        try{
            $userObj = User::find($request->id);
//            $total_credit = $userObj->remaining_credits + $request->remaining_credits;
            $userObj->remaining_credits = $request->remaining_credits;
            $userObj->temp_remaining_credits = $request->remaining_credits;
            if ($userObj->save()) {
                $User = new User;
                $dataResult = $User->updateUserCreditSendEmail($userObj);

                session()->flash('success', 'Credit Add Successfully');
                return redirect()->back();
            }
            session()->flash('error', 'Credit Add Fail Please Try Again');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
        }
    }
}
