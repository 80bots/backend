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
	    public function index()
	    {
	    	$user_id = Auth::user()->id;
	    	$results = SchedulingInstance::findByUserId($user_id)->get();
	    	return view('user.scheduling.index',compact('results'));
	    }

		public function create()
		{
			$user_id = Auth::user()->id;

			$instances = UserInstances::where(['status' => 'stop','user_id'=>$user_id])->get();
			return view('user.scheduling.create',compact('instances'));
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
			    $schedulingInstance->user_id = $user_id;
			    $schedulingInstance->current_time_zone = '+5.30';
			    $schedulingInstance->created_at = date('Y-m-d H:i:s');
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
		    $user_id = Auth::user()->id;
			$instances = UserInstances::where(['status' => 'stop','user_id'=>$user_id])->get();

			$scheduling = SchedulingInstance::find($id);

			return view('user.scheduling.edit',compact('scheduling','instances' ,'id'));
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
		    dd($request);
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
	}
