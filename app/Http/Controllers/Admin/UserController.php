<?php

namespace App\Http\Controllers\Admin;

use App\CreditUsage;
use App\Helpers\CreditUsageHelper;
use App\Helpers\MailHelper;
use App\Helpers\QueryHelper;
use App\Http\Controllers\AppController;
use App\Http\Resources\Admin\UserCollection;
use App\Http\Resources\Admin\UserResource;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UserController extends AppController
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

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');
            $sort   = $request->input('sort');
            $order  = $request->input('order') ?? 'asc';

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

            //
            if (! empty($search)) {
                $resource->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            }

            $resource->when($sort, function ($query, $sort) use ($order) {
                if (! empty(User::ORDER_FIELDS[$sort])) {
                    $result = QueryHelper::orderUser($query, User::ORDER_FIELDS[$sort], $order);
                    return $result->where('users.id', '!=', Auth::id());
                    //return $result;
                } else {
                    return $query->where('id', '!=', Auth::id())->orderBy('created_at', 'desc');
                    //return $query->orderBy('created_at', 'desc');
                }
            }, function ($query) {
                return $query->where('id', '!=', Auth::id())->orderBy('created_at', 'desc');
                //return $query->orderBy('created_at', 'desc');
            });

            $users  = (new UserCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $users->meta ?? null;

            $response = [
                'data'  => $users->data ?? [],
                'total' => $meta->total ?? 0
            ];

            return $this->success($response);

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
        try {
            $updateData = $request->validate([
               'update.status' => 'in:active,inactive',
               'update.credits' => 'integer'
            ]);

            $user = User::find($id);

            foreach ($updateData['update'] as $key => $value) {
                switch ($key) {
                    case 'status':
                        $user->status = $value;
                        $user->verification_token = '';
                        if ($user->save()) {
                            return $this->success((new UserResource($user))->toArray($request));
                        }
                        break;
                    case 'credits':

                        $addCredits = $value - $user->credits;

                        $update = $user->update([
                            'credits' => $value
                        ]);

                        if ($update) {
                            //
                            Log::debug("adminAddCredit");
                            CreditUsageHelper::adminAddCredit($user, $addCredits);
                            //
                            MailHelper::updateUserCreditSendEmail($user);
                            return $this->success(
                                (new UserResource($user))->toArray($request),
                                __('admin.users.credit_added_success')
                            );
                        }
                        break;
                }
            }

            return $this->error('System Error', 'Cannot update user at this moment');
        } catch (\Exception $exception){
            return $this->error('System Error', $exception->getMessage());
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
        //
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
                        'credits' => $request->input('credits')
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
