<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppController;
use Illuminate\Http\Request;

class NotificationController extends AppController
{
	public function index()
    {
		return $this->success();
	}

    public function store(Request $request)
    {
        return $this->success();
    }

    public function show($id)
    {
        return $this->success();
    }

    public function update(Request $request)
    {
        return $this->success();
    }

    public function destroy($id)
    {
        return $this->success();
    }
}
