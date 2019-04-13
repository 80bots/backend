<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use App\AwsConnection;
	use App\BaseModel;
	use App\Notifications;
	use App\User;
	use App\UserInstances;
	use App\SchedulingInstance;

	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Log;

	class SchedulingInstancesController extends Controller
	{

		public function __construct()
	    {
	        // echo 'dsfdsf';exit;
	    }
		
		

	    public function index()
	    {
		    try {	
		    	$user_id = Auth::user()->id;
		    	$results = SchedulingInstance::findByUserId($user_id)->get();

		    	// dd($results);
		    	return view('user.scheduling.index',compact('results'));
	    	}
	        catch (\Exception $e) {
	            session()->flash('error', $e->getMessage());
	            return redirect(route('user.scheduling.index'));
	        } 
	    }

		public function create()
		{
			try {
				$user_id = Auth::user()->id;

				$instances = UserInstances::where(['status' => 'stop','user_id'=>$user_id])->get();
				return view('user.scheduling.create',compact('instances'));
			}
	        catch (\Exception $e) {
	            session()->flash('error', $e->getMessage());
	            return redirect(route('user.scheduling.index'));
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
			try {
				$user_id = Auth::user()->id;

				$schedulingInstance = new SchedulingInstance();

			    $schedulingInstance->user_instances_id = $request->user_instances_id;
			    $schedulingInstance->start_time = $request->start_time;
			    $schedulingInstance->end_time = $request->end_time;
			    $schedulingInstance->utc_start_time = $request->utc_start_time ;
			    $schedulingInstance->utc_end_time = $request->utc_end_time;
			    $schedulingInstance->user_id = $user_id;
			    $schedulingInstance->status = $request->status;
			    $schedulingInstance->current_time_zone =  $request->current_time_zone;
			    $schedulingInstance->created_at = date('Y-m-d H:i:s');

			    // dd($schedulingInstance);
			    
			 	if($schedulingInstance->save()){
			 		session()->flash('success', 'Scheduling Create successfully');
			 		return redirect(route('user.scheduling.index'));
			 	}
			 	else
			 	{
			 		session()->flash('error', 'Please Try again later');
           			return redirect(route('user.scheduling.index'));
			 	}
			}
	        catch (\Exception $e) {
	            session()->flash('error', $e->getMessage());
	            return redirect(route('user.scheduling.index'));
	        }    
		}

		/**
		 * Display the specified resource.
		 *
		 * @param  int  $id
		 * @return \Illuminate\Http\Response
		 */
		public function show($id)
		{
		    
		}

		/**
		 * Show the form for editing the specified resource.
		 *
		 * @param  int  $id
		 * @return \Illuminate\Http\Response
		 */
		public function edit($id)
		{
			try{
			    $user_id = Auth::user()->id;
				$instances = UserInstances::where(['status' => 'stop','user_id'=>$user_id])->get();

				$scheduling = SchedulingInstance::with('userInstances')->find($id);

				return view('user.scheduling.edit',compact('scheduling','instances' ,'id'));
			} catch (\Exception $exception){
	            session()->flash('error', $exception->getMessage());
	            return redirect()->back();
	        }
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
			try{
			    $schedulingInstance = SchedulingInstance::find($id);
			    $schedulingInstance->user_instances_id = $request->user_instances_id;
			    $schedulingInstance->start_time = $request->start_time;
				$schedulingInstance->end_time = $request->end_time;
				$schedulingInstance->utc_start_time = $request->utc_start_time ;
			    $schedulingInstance->utc_end_time = $request->utc_end_time;
				$schedulingInstance->status = $request->status;
				$schedulingInstance->current_time_zone =  $request->current_time_zone;
				
			    if($schedulingInstance->save()){

			    	return redirect(route('user.scheduling.index'))->with('success', 'Scheduling Update Successfully');
				}
				else
				{
					session()->flash('error', 'Bot Can not Updated Successfully');
            		return redirect()->back();
				}
			} catch (\Exception $exception){
	            session()->flash('error', $exception->getMessage());
	            return redirect()->back();
	        }
		}

		/**
		 * Remove the specified resource from storage.
		 *
		 * @param  int  $id
		 * @return \Illuminate\Http\Response
		 */
		public function destroy($id)
		{
		   	try{
	            $schedulingInstance = SchedulingInstance::find($id);
		       	if($schedulingInstance->delete()){
	                return redirect(route('user.scheduling.index'))->with('success', 'Scheduling Delete Successfully');
            	}
	            session()->flash('error', 'Scheduling Can not Deleted Successfully');
	            return redirect()->back();
        	} catch (\Exception $exception){
	            session()->flash('error', $exception->getMessage());
	            return redirect()->back();
        	}
		}

		public function changeStatus(Request $request){
        	try{

	            $Scheduling = SchedulingInstance::find($request->id);
	           
	            $Scheduling->status = $request->status;

	            if($Scheduling->save()){

	            	return 'true';
	            }
	            else
	            {
	            	return 'false';
	            }

	        } catch (\Exception $e){
	            session()->flash('error', $e->getMessage());
	            
	        }
    	}
	}
