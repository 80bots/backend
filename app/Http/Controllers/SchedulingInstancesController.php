<?php

	namespace App\Http\Controllers;

	use App\SchedulingInstancesDetails;
    use DateTime;
    use DateTimeZone;
    use Illuminate\Http\Request;
	use App\AwsConnection;
	use App\BaseModel;
	use App\Notifications;
	use App\User;
	use App\UserInstances;
	use App\SchedulingInstance;

	use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Log;
    use PhpParser\Node\Expr\New_;

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

		public function CheckScheduled($id){
            try {
                $user_id = Auth::user()->id;
                $scheduleInstanceObj = SchedulingInstance::findByUserInstanceId($id, $user_id)->first()->toArray();

                $return['status'] = 'true';
                $return['data'] = $scheduleInstanceObj;
                return json_encode($return);
            } catch (\Exception $e) {
                $return['status'] = 'false';
                return $return;
            }
        }

		/**
		* Store a newly created resource in storage.
		*
		* @param  \Illuminate\Http\Request  $request
		* @return \Illuminate\Http\Response
		*/

        public function convertTimeToUTCzone($str, $userTimezone, $format = 'h:i A'){
            $new_str = new DateTime($str, new DateTimeZone(  $userTimezone  ) );
            $new_str->setTimeZone(new DateTimeZone('UTC'));
            return $new_str->format($format);
        }

        public function convertTimeToUSERzone($str, $userTimezone, $format = 'Y-m-d h:i:s A'){
            if(empty($str)){
                return '';
            }
            $new_str = new DateTime($str, new DateTimeZone('UTC') );
            $new_str->setTimeZone(new DateTimeZone( $userTimezone ));
            return $new_str->format( $format);
        }

		public function store(Request $request)
		{
			try {
				$user_id = Auth::user()->id;
				$userInstanceId = isset($request->instance_id) ? $request->instance_id : '';
				$userTimeZone = isset($request->userTimeZone) ? $request->userTimeZone : '';
				$days = isset($request->day) ? $request->day : '';
				$requestData = [];
				foreach ($days as $key => $day){
				    if(!empty($day)){
                        $data = [];
                        $data['day'] = $day;
                        $ids = isset($request->ids) ? explode(',',$request->ids[$key]) : '';
                        $startTime = isset($request->start_time) ? $request->start_time : '';
                        $endTime = isset($request->end_time) ? $request->end_time : '';
                        $startAside = isset($request->start_aside) ? $request->start_aside : '';
                        $endAside = isset($request->end_aside) ? $request->end_aside : '';
                        if(!empty($startTime) && !empty($startAside)){
                            $data['schedule_type'] = 'start';
                            if(!empty($startTime[$key]) && !empty($startAside[$key])){
//                                $data['selected_time'] = date('h:i A', strtotime($startTime[$key].$startAside[$key]));
                                $data['selected_time'] = $this->convertTimeToUTCzone($startTime[$key].$startAside[$key], $userTimeZone);
                            } else {
                                $data['selected_time'] = '';
                            }
                            if(!empty($ids) && $ids[0] != "0"){
                                $data['id'] = $ids[0];
                            }
                            array_push($requestData, $data);
                        }

                        if(!empty($endTime) && !empty($endAside)){
                            $data['schedule_type'] = 'stop';
                            if(!empty($endTime[$key]) && !empty($endAside[$key])){
//                                $data['selected_time'] = date('h:i A', strtotime($endTime[$key].$endAside[$key]));
                                $data['selected_time'] = $this->convertTimeToUTCzone($endTime[$key].$endAside[$key], $userTimeZone);
                            } else {
                                $data['selected_time'] = '';
                            }
                            if(!empty($ids) && $ids[1] != "0"){
                                $data['id'] = $ids[1];
                            }
                            array_push($requestData, $data);
                        }
                    }
                }

                $schedulingInstance = SchedulingInstance::findByUserInstanceId($userInstanceId, $user_id)->first();
				if(empty($schedulingInstance)){
                    $schedulingInstance = new SchedulingInstance();
                }
			    $schedulingInstance->user_id = $user_id;
			    $schedulingInstance->user_instances_id = $userInstanceId;
			 	if($schedulingInstance->save()){
			 	    foreach ($requestData as $scheduleDetail){
			 	        if(isset($scheduleDetail['id']) && !empty($scheduleDetail['id'])){
			 	            $schedulingInstanceDetail = SchedulingInstancesDetails::findById($scheduleDetail['id'])->first();
                        } else {
			 	            $schedulingInstanceDetail = new SchedulingInstancesDetails();
                        }
                        $schedulingInstanceDetail->scheduling_instances_id = $schedulingInstance->id;
                        $schedulingInstanceDetail->schedule_type = $scheduleDetail['schedule_type'];
                        $schedulingInstanceDetail->day = $scheduleDetail['day'];
                        $schedulingInstanceDetail->selected_time = $scheduleDetail['selected_time'];
			 	        $schedulingInstanceDetail->save();
                    }
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
                    session()->flash('success', 'Schedule '.$request->status.' successfully!');
	            	return 'true';
	            }
	            else
	            {
                    session()->flash('error', 'Schedule '.$request->status.' Not successfully!');
	            	return 'false';
	            }

	        } catch (\Exception $e){
                session()->flash('error', 'Schedule '.$request->status.' Not successfully!');
                return 'false';
	        }
    	}
	}
