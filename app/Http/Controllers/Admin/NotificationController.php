<?php

namespace App\Http\Controllers\Admin;

use App\CreditPercentage;
use App\Http\Controllers\AppController;
use Exception;
use Illuminate\Http\Request;

class NotificationController extends AppController
{
	public function index()
    {
		$percentages = CreditPercentage::all();
		return $this->success($percentages);
	}

	/* display form to add credit percentage */
    public function create()
    {
    	return view('admin.notification.add');
    }

    /* store percentage */
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

    /* show credit percentage */
    public function show($id)
    {

    }

    /* display edit form */
    public function edit($id)
    {
		$percentage = CreditPercentage::findOrFail($id);
		return view('admin.notification.edit',compact('percentage'));
    }

    /* update credit percentage */
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
