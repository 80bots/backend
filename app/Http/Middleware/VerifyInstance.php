<?php

namespace App\Http\Middleware;

use App\BotInstance;
use App\Helpers\UserHelper;
use App\User;
use Closure;
use Illuminate\Http\Request;

class VerifyInstance
{
    /**
     * Handle an incoming request.
     * If request's headers contains bot-instance-id property, then we try to authorize the instance using owner's data
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function handle(Request $request, Closure $next)
    {
        $instance_id = $request->header('bot-instance-id');
        if($instance_id) {
            $token = $this->getInstanceBearerTokenForInstance($instance_id);
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }
        return $next($request);
    }

    /**
     * @param $aws_instance_id
     * @return mixed|null
     */
    public function getInstanceBearerTokenForInstance ($aws_instance_id) {
        $instance = BotInstance::withTrashed()
            ->findByInstanceId($aws_instance_id)
            ->first();
        if(!$instance) return null;
        /** @var User $user */
        $user = $instance->user;
        $result = UserHelper::getUserToken($user);
        return $result['token'];
    }
}
