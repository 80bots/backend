<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\MailHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\UserCollection;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UsersController extends AppController
{
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return UserCollection
     */
    public function index(Request $request)
    {
        try {

            $resource = User::ajax();

            // TODO: Add Filters

            switch ($request->input('role')) {
                case 'users':
                    $resource->onlyUsers();
                    break;
                case 'admins':
                    $resource->onlyAdmins();
                    break;
            }

            return new UserCollection($resource->paginate(self::PAGINATE));

        } catch (Throwable $throwable) {
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
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

    public function changeStatus(Request $request)
    {
        try{
            $userObj = User::find($request->id);
            $userObj->status = $request->status;
            $userObj->verification_token = '';
            if($userObj->save()){
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateCredit(Request $request): JsonResponse
    {
        try{

            if (! empty($request->input('credits')) && ! empty($request->input('id'))) {
                $update = User::where('id', '=', $request->input('id'))
                    ->update([
                        'remaining_credits'         => $request->input('credits'),
                        'temp_remaining_credits'    => $request->input('credits'),
                    ]);

                if (! empty($update)) {
                    MailHelper::updateUserCreditSendEmail(User::find($request->input('id')));
                    return $this->success([], __('admin.users.credit_added_success'));
                }

                return $this->error([], __('admin.users.credit_added_error'));
            }

            return $this->error([], __('admin.parameters_incorrect'));

        } catch (Throwable $throwable){
            return $this->error(__('admin.server_error'), $throwable->getMessage());
        }
    }
}
