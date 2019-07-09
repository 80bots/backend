<?php

namespace App\Http\Controllers\admin;

use App\CreditPercentage;
use App\Http\Controllers\AppController;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreditPercentController extends AppController
{
		
	public function index(){
		$percentages = CreditPercentage::all();
		return view('admin.percent.list',compact('percentages'));
	}

	/* display form to add credit percentage */
    public function create(){
    	return view('admin.percent.add');
    }

    /* store percentage */
    public function store(Request $request){

		$request->validate([
		    'percentage' => 'required|unique:credit_percentages',
		]);	

		$percentage = new CreditPercentage();
		$percentage->percentage = $request->percentage;
		if($percentage->save()){
			return redirect()->route('percent.index')->with('success','Credit Percentage Successfully Added.');
		} else {
			return redirect()->back()->with('error','Credit Percentage Not Added.');
		}

    }

    /* show credit percentage */
    public function show($id){

    }


    /* display edit form */
    public function edit($id){

		$percentage = CreditPercentage::findOrFail($id);
		return view('admin.percent.edit',compact('percentage'));

    }

    /* update credit percentage */
    public function update(Request $request){  	

		$request->validate([
		    'percentage' => 'required|unique:credit_percentages,percentage,'.$request->id,
		]);	

		$percentage = CreditPercentage::findOrFail($request->id);
		$percentage->percentage = $request->percentage;
		if($percentage->save()){
			return redirect()->route('percent.index')->with('success','Credit Percentage Successfully Updated.');
		}

    }

    /* delete credit percentages */
    public function destroy($id){
    	
    	try {

    		$percentage = CreditPercentage::findOrFail($id);
    		if($percentage->delete()){
    			return redirect()->back()->with('success','Credit Percentage Successfully Deleted.');
    		}
    	}

    	catch(Exception $e){
    		return redirect()->back()->with('error',$e->getMessage());
    	}
    }

}
