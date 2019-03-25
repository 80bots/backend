<?php

namespace App\Http\Controllers\admin;

use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Session;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $userListObj = User::get();
            if($userListObj){
                return view('admin.user.index',compact('userListObj'));
            } else {
                return view('admin.user.index');
            }
        } catch (\Exception $exception){
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
            if($userObj->save()){
                Session::flash('success', 'Status Successfully Change');
                return 'true';
            }
            Session::flash('error', 'Status Change Fail Please Try Again');
            return 'false';
        } catch (\Exception $exception){
            Session::flash('error', $exception->getMessage());
            return 'false';
        }
    }

    public function updateCredit(Request $request){
        try{
            $userObj = User::find($request->id);
            $userObj->credit_score = $userObj->credit_score + $request->credit_score;
            if ($userObj->save()){
                Session::flash('success', 'Credit Add Successfully');
                return redirect()->back();
            }
            Session::flash('error', 'Credit Add Fail Please Try Again');
            return redirect()->back();
        } catch (\Exception $exception){
            Session::flash('error', $exception->getMessage());
            return redirect()->back();
        }
    }
}
