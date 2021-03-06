<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QueryHelper
{
    const ENTITY_USER                   = 'ENTITY_USER';
    const ENTITY_BOT_INSTANCES          = 'ENTITY_BOT_INSTANCES';
    const ENTITY_BOT_INSTANCES_UPTIME   = 'ENTITY_BOT_INSTANCES_UPTIME';
    const ENTITY_BOT_INSTANCES_DETAILS  = 'ENTITY_BOT_INSTANCES_DETAILS';
    const ENTITY_AWS_REGION             = 'ENTITY_AWS_REGION';
    const ENTITY_BOT                    = 'ENTITY_BOT';
    const ENTITY_SCHEDULING             = 'ENTITY_SCHEDULING';

    /**
     * @param Builder $query
     * @param array $sort
     * @param string $order
     * @return Builder
     */
    public static function orderBotInstance(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_AWS_REGION:
                $query->leftJoin('aws_regions', function ($join) {
                    $join->on('bot_instances.aws_region_id', '=', 'aws_regions.id');
                })
                ->orderBy("aws_regions.{$sort['field']}", $order)
                ->select('bot_instances.*');
                break;
            case self::ENTITY_BOT:
                $query->leftJoin('bots', function ($join) {
                    $join->on('bot_instances.bot_id', '=', 'bots.id');
                })
                ->orderBy("bots.{$sort['field']}", $order)
                ->select('bot_instances.*');
                break;
            case self::ENTITY_BOT_INSTANCES:
                $query->orderBy("{$sort['field']}", $order);
                break;
            case self::ENTITY_BOT_INSTANCES_UPTIME:
                $query->orderBy(DB::raw("`total_up_time` + `cron_up_time`"), $order);
                break;
            case self::ENTITY_BOT_INSTANCES_DETAILS:
                break;
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $sort
     * @param string $order
     * @return Builder
     */
    public static function orderBot(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_BOT:
                $query->orderBy("{$sort['field']}", $order);
                break;
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $sort
     * @param string $order
     * @return Builder
     */
    public static function orderBotScheduling(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_SCHEDULING:
                $query->orderBy("{$sort['field']}", $order);
                break;
            case self::ENTITY_BOT_INSTANCES:
                $query->leftJoin('bot_instances', function ($join) {
                    $join->on('scheduling_instances.instance_id', '=', 'bot_instances.id');
                })
                    ->orderBy("bot_instances.{$sort['field']}", $order)
                    ->select('scheduling_instances.*');
                break;
            case self::ENTITY_USER:
                $query->leftJoin('users', function ($join) {
                    $join->on('scheduling_instances.user_id', '=', 'users.id');
                })
                    ->orderBy("users.{$sort['field']}", $order)
                    ->select('scheduling_instances.*');
                break;
        }
        return $query;
    }

    /**
     * @param Builder $query
     * @param array $sort
     * @param string $order
     * @return Builder
     */
    public static function orderAwsRegion(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_AWS_REGION:
                $query->orderBy("{$sort['field']}", $order);
                break;
        }
        return $query;
    }
}
