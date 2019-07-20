<?php

namespace App\Http\Controllers\admin;

use App\Bots;
use App\BotTags;
use App\Http\Controllers\AppController;
use App\Platforms;
use App\Tags;
use Illuminate\Http\Request;
use Auth;
use App\UserInstances;
use App\UserInstancesDetails;

class BotsController extends AppController
{
    public $limit;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bots = Bots::all();
        if(!$bots->count()){
          session()->flash('error', 'Bots Not Found');
        }
        return view('admin.bots.index',compact('bots'));
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
            $botObj->bot_name = isset($request->bot_name) ? $request->bot_name : '';
            $botObj->description = isset($request->description) ? $request->description : '';
            $botObj->aws_ami_image_id = isset($request->aws_ami_image_id) ? $request->aws_ami_image_id : '';
            $botObj->aws_ami_name = isset($request->aws_ami_name) ? $request->aws_ami_name : '';
            $botObj->aws_instance_type = isset($request->aws_instance_type) ? $request->aws_instance_type : '';
            $botObj->aws_startup_script = isset($request->aws_startup_script) ? $request->aws_startup_script : '';
            $botObj->aws_custom_script = isset($request->aws_custom_script) ? $request->aws_custom_script : '';
            $botObj->aws_storage_gb = isset($request->aws_storage_gb) ? $request->aws_storage_gb : '';

            $platform_name = isset($request->Platform) ? $request->Platform : '';
            $platformObj = Platforms::findByName($platform_name);
            if(empty($platformObj)){
                $platformObj = new Platforms();
                $platformObj->name = $platform_name;
                $platformObj->save();
            }
            $botObj->platform_id = $platformObj->id;

            if($botObj->save()){
                if(!empty($request->tags) && isset($request->tags)){
                    $tagString = rtrim($request->tags,',');
                    $tags = explode(',', $tagString);
                    foreach ($tags as $tag){
                        $tagObj = Tags::findByName($tag);
                        if(!isset($tagObj) && empty($tagObj)){
                            $tagObj = new Tags();
                            $tagObj->name = $tag;
                            $tagObj->save();
                        }
                        $botTagsObj = New BotTags();
                        $botTagsObj->bots_id = $botObj->id;
                        $botTagsObj->tags_id = $tagObj->id;
                        $botTagsObj->save();
                    }
                }
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
            $tagsArray = [];
            if(isset($bots->botTags) && !empty($bots->botTags)){
                foreach ($bots->botTags as $tag){
                    array_push($tagsArray, $tag->tags->name);
                }
            }
            $tags = implode(',',$tagsArray);
            $platforms = Platforms::get();
            if(isset($bots) && !empty($bots)){
                return view('admin.bots.edit',compact('platforms','bots', 'id', 'tags'));
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
            $botObj->bot_name = isset($request->bot_name) ? $request->bot_name : '';
            $botObj->description = isset($request->description) ? $request->description : '';
            $botObj->aws_ami_image_id = isset($request->aws_ami_image_id) ? $request->aws_ami_image_id : '';
            $botObj->aws_ami_name = isset($request->aws_ami_name) ? $request->aws_ami_name : '';
            $botObj->aws_instance_type = isset($request->aws_instance_type) ? $request->aws_instance_type : '';
            $botObj->aws_startup_script = isset($request->aws_startup_script) ? $request->aws_startup_script : '';
            $botObj->aws_custom_script = isset($request->aws_custom_script) ? $request->aws_custom_script : '';
            $botObj->aws_storage_gb = isset($request->aws_storage_gb) ? $request->aws_storage_gb : '';

            $platform_name = isset($request->Platform) ? $request->Platform : '';
            $platformObj = Platforms::findByName($platform_name);
            if(empty($platformObj)){
                $platformObj = new Platforms();
                $platformObj->name = $platform_name;
                $platformObj->save();
            }
            $botObj->platform_id = $platformObj->id;

            if($botObj->save()){
                if(!empty($request->tags) && isset($request->tags)){
                    BotTags::deleteByBotId($id);
                    $tagString = rtrim($request->tags,',');
                    $tags = explode(',', $tagString);
                    foreach ($tags as $tag){
                        $tagObj = Tags::findByName($tag);
                        if(!isset($tagObj) && empty($tagObj)){
                            $tagObj = new Tags();
                            $tagObj->name = $tag;
                            $tagObj->save();
                        }
                        $botTagsObj = New BotTags();
                        $botTagsObj->bots_id = $botObj->id;
                        $botTagsObj->tags_id = $tagObj->id;
                        $botTagsObj->save();
                    }
                }
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

    public function changeStatus(Request $request)
    {
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

    public function list($platformId = null)
    {
        if(!$platformId) {
          $this->limit = 5;
        }

        $platforms = new Platforms;

        $platforms = $platforms->hasBots($this->limit, $platformId)->paginate(5);

        return view('admin.bots.list',compact('platforms'));
    }

    public function mineBots()
    {
        $userId = Auth::id();
        $instancesId = [];

        $userInstances = UserInstances::findByUserId($userId)->get();
        $bots = Bots::all();

        if(!$userInstances->count()) {
          session()->flash('error', 'Instance Not Found');
        }

        return view('admin.instance.my-bots', compact('userInstances', 'bots'));
    }

}
