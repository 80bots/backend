<?php

namespace App\Http\Controllers\admin;

use App\Bots;
use App\Http\Controllers\AppController;
use App\Platforms;
use Illuminate\Http\Request;

class BotsController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{
            $botLists = Bots::all();
            if(!$botLists->isEmpty()){
                return view('admin.bots.index',compact('botLists'));
            }
            session()->flash('error', 'Bots Not Found');
            return view('admin.bots.index');
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return view('admin.bots.index');
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try{
            $platforms = Platforms::get();
            return view('admin.bots.create',compact('platforms'));
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return view('admin.bots.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $botObj = new Bots();
            $botObj->platform_id = isset($request->Platform) ? $request->Platform : '';
            $botObj->bot_name = isset($request->bot_name) ? $request->bot_name : '';
            $botObj->description = isset($request->description) ? $request->description : '';
            $botObj->aws_ami_image_id = isset($request->aws_ami_image_id) ? $request->aws_ami_image_id : '';
            $botObj->aws_ami_name = isset($request->aws_ami_name) ? $request->aws_ami_name : '';
            $botObj->aws_instance_type = isset($request->aws_instance_type) ? $request->aws_instance_type : '';
            $botObj->aws_startup_script = isset($request->aws_startup_script) ? $request->aws_startup_script : '';
            $botObj->aws_storage_gb = isset($request->aws_storage_gb) ? $request->aws_storage_gb : '';
            if($botObj->save()){
                return redirect(route('admin.bots.index'))->with('success', 'Bot Added Successfully');
            }
            session()->flash('error', 'Bot Can not Added Successfully');
            return redirect()->back();
        } catch(\Exception $exception) {
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Bots  $bots
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try{
            $bots = Bots::find($id);
            if(isset($bots) && !empty($bots)){
                return view('admin.bots.view',compact('bots', 'id'));
            }
            session()->flash('error', 'Please Try Again');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Bots  $bots
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try{
            $bots = Bots::find($id);
            $platforms = Platforms::get();
            if(isset($bots) && !empty($bots)){
                return view('admin.bots.edit',compact('platforms','bots', 'id'));
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
     * @param  \App\Bots  $bots
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{
            $botObj = Bots::find($id);
            $botObj->platform_id = isset($request->Platform) ? $request->Platform : '';
            $botObj->bot_name = isset($request->bot_name) ? $request->bot_name : '';
            $botObj->description = isset($request->description) ? $request->description : '';
            $botObj->aws_ami_image_id = isset($request->aws_ami_image_id) ? $request->aws_ami_image_id : '';
            $botObj->aws_ami_name = isset($request->aws_ami_name) ? $request->aws_ami_name : '';
            $botObj->aws_instance_type = isset($request->aws_instance_type) ? $request->aws_instance_type : '';
            $botObj->aws_startup_script = isset($request->aws_startup_script) ? $request->aws_startup_script : '';
            $botObj->aws_storage_gb = isset($request->aws_storage_gb) ? $request->aws_storage_gb : '';
            if($botObj->save()){
                return redirect(route('admin.bots.index'))->with('success', 'Bot Update Successfully');
            }
            session()->flash('error', 'Bot Can not Updated Successfully');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Bots  $bots
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $botObj = Bots::find($id);
            if($botObj->delete()){
                return redirect(route('admin.bots.index'))->with('success', 'Bot Delete Successfully');
            }
            session()->flash('error', 'Bot Can not Deleted Successfully');
            return redirect()->back();
        } catch (\Exception $exception){
            session()->flash('error', $exception->getMessage());
            return redirect()->back();
        }
    }

    public function ChangeStatus(Request $request){
        try{
            $botObj = Bots::find($request->id);
            $botObj->status = $request->status;
            if($botObj->save()){
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
