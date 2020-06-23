<?php

namespace App\Http\Controllers;

use App\Http\Resources\User\TimezoneCollection;
use App\Mail\Support;
use App\Timezone;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Illuminate\Support\Facades\Mail;

class UserController extends AppController
{
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
                return $this->success();
            }


            return $this->error('System Error', 'Cannot update profile at this moment');
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function feedback(Request $request)
    {
        try {
            $data = $request->validate([
                'type'       => 'string|required',
                'category'   => 'string|required',
                'message'    => 'string|required'
            ]);

            Mail::to('support@80bots.com')->send(new Support($request->user(), $data));

            return $this->success();
        } catch (Throwable $throwable) {
            return $this->error(__('user.server_error'), $throwable->getMessage(), 500);
        }
    }
}
