<?php

namespace App\Http\Controllers\Admin;

use App\CreditPercentage;
use App\Http\Controllers\AppController;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NotificationController extends AppController
{
    /**
     * @return JsonResponse
     */
	public function index()
    {
		$percentages = CreditPercentage::all();
		return $this->success($percentages);
	}

	/* display form to add credit percentage */
    /**
     * @return Application|Factory|View
     */
    public function create()
    {
    	return view('admin.notification.add');
    }

    /* store percentage */
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
		$data = $request->validate([
		    'percentage' => 'required|unique:credit_percentages',
		]);
		$percentage = new CreditPercentage;
		$percentage->percentage = $data['percentage'];

		if($percentage->save()){
			return $this->success($percentage->toArray());
		} else {
            return $this->error('System Error', 'Cannot add notification right now');
		}
    }

    /* display edit form */
    /**
     * @param $id
     * @return Application|Factory|View
     */
    public function edit($id)
    {
		$percentage = CreditPercentage::findOrFail($id);
		return view('admin.notification.edit',compact('percentage'));
    }

    /* update credit percentage */
    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
		$request->validate([
		    'percentage' => 'required|unique:credit_percentages,percentage,'.$request->id,
		]);

		$percentage = CreditPercentage::findOrFail($request->id);
		$percentage->percentage = $request->percentage;
		if($percentage->save()){
			return redirect()->route('admin.notification.index')->with('success','Credit Percentage Successfully Updated.');
		}
    }

    /* delete credit percentages */
    /**
     * @param $id
     * @return Application|ResponseFactory|JsonResponse|Response
     */
    public function destroy($id)
    {
    	try {
    		$percentage = CreditPercentage::findOrFail($id);
    		if($percentage->delete()){
    			return response(null, 200);
    		}
    	}
    	catch(Exception $e){
    		return $this->error('System Error', 'Cannot delete notification right now');
    	}
    }
}
