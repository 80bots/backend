<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\AwsConnectionController;
use App\UserInstances;
use App\UserInstancesDetails;
use Illuminate\Http\Request;

class UserInstancesController extends AwsConnectionController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        try
        {
            $UserInstance = UserInstances::findByUserId($id)->get();
            if(!$UserInstance->isEmpty()){
                return view('admin.instance.index',compact('UserInstance'));
            }
            session()->flash('error', 'Instance Not Found');
            return view('admin.instance.index');
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return view('admin.instance.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        dd($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function show(UserInstances $userInstances)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function edit(UserInstances $userInstances)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserInstances $userInstances)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserInstances $userInstances)
    {
        //
    }

    public function changeStatus(Request $request){
        try{
            $instanceObj = UserInstances::find($request->id);
            $instanceDetail = UserInstancesDetails::where(['user_instance_id' => $request->id])->latest()->first();
            $instanceIds = [];
            array_push($instanceIds, $instanceObj->aws_instance_id);
            $currentDate = date('Y-m-d H:i:s');

            $describeInstancesResponse = $this->DescribeInstances($instanceIds);
            $reservationObj = $describeInstancesResponse->getPath('Reservations');
            if(empty($reservationObj)){
                $instanceObj->status = 'terminated';
                $instanceObj->save();
                session()->flash('error', 'This instance is not exist');
                return 'false';
            }
            $InstStatus = $reservationObj[0]['Instances'][0]['State']['Name'];
            if($InstStatus == 'terminated'){
                $instanceObj->status = 'terminated';
                $instanceObj->save();
                session()->flash('error', 'This instance is already terminated');
                return 'false';
            }

            if($request->status == 'start'){
                $instanceObj->status = 'running';
                $startObj = $this->StartInstance($instanceIds);
                $instanceDetail = new UserInstancesDetails();
                $instanceDetail->user_instance_id = $request->id;
                $instanceDetail->start_time = $currentDate;
                $instanceDetail->save();

            } elseif($request->status == 'stop') {
                $instanceObj->status = 'stop';
                $stopObj = $this->StopInstance($instanceIds);
                $instanceDetail->end_time = $currentDate;
                $deffTime = UserInstances::deffTime($instanceDetail->start_time, $instanceDetail->end_date);
                $instanceDetail->total_time = $deffTime;
                if($instanceDetail->save()){
                    $instanceObj->up_time = $instanceObj->up_time + $deffTime;
                }
            } else {
                $instanceObj->status = 'terminated';
                $terminateInstance = $this->TerminateInstance($instanceIds);
            }

            if($instanceObj->save()){
                session()->flash('success', 'Instance '.$request->status.' successfully!');
                return 'true';
            }
            session()->flash('error', 'Instance '.$request->status.' Not successfully!');
            return 'false';
        } catch (\Exception $e){
            session()->flash('error', $e->getMessage());
            return 'false';
        }
    }
}
