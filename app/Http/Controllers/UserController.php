<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\UserCollection;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UsersResource;
use App\Http\Resources\User\TimezoneCollection;
use App\Timezone;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

class UserController extends AppController
{
    const PAGINATE = 1;

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return UserCollection|JsonResponse
     */
    public function index(Request $request)
    {
        try {

            $limit  = $request->query('limit') ?? self::PAGINATE;
            $search = $request->input('search');

            $resource = User::query();

            if (! empty($search)) {
                $resource->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            }

            $users  = (new UserCollection($resource->paginate($limit)))->response()->getData();
            $meta   = $users->meta ?? null;

            $response = [
                'data'  => $users->data ?? [],
                'total' => $meta->total ?? 0,
            ];

            return $this->success($response);

        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * Display the specified resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {

            $user = User::find(Auth::id());

            return $this->success([
                'user'          => $user,
                'timezones'     => (new TimezoneCollection(Timezone::get()))->response()->getData()
            ]);
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        try {

            $updateData = $request->validate([
                'update.timezone_id' => 'integer',
                'update.region_id'   => 'integer'
            ]);

            foreach ($updateData['update'] as $key => $value) {
                switch ($key) {
                    case 'timezone_id':
                        $request->user()->timezone_id = $value;
                        break;
                    case 'region_id':
                        $request->user()->region_id = $value;
                        break;
                }
            }

            if ($request->user()->save()) {
                $user = (new UserResource(User::find(Auth::id())))->response()->getData();
                return $this->success([ 'user' => $user->data ?? null ]);
            }

            return $this->error('System Error', 'Cannot update profile at this moment');
        } catch (\Exception $exception){
            return $this->error('System Error', $exception->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return JsonResponse|Response
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $updateData = $request->validate([
                'update.status' => 'in:active,inactive',
            ]);

            $user = User::find($id);

            foreach ($updateData['update'] as $key => $value) {
                switch ($key) {
                    case 'status':
                        $user->status = $value;
                        $user->verification_token = '';
                        if ($user->save()) {
                            return $this->success((new UsersResource($user))->toArray($request));
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
     * Get list timezones
     * @return JsonResponse
     */
    public function getTimezones(): JsonResponse
    {
        try {
            return $this->success((new TimezoneCollection(Timezone::all()))->response()->getData());
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage());
        }
    }
}
