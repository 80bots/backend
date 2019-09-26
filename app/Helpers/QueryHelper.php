<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class QueryHelper
{
    const ENTITY_USER                   = 'ENTITY_USER';
    const ENTITY_ROLE                   = 'ENTITY_ROLE';
    const ENTITY_BOT_INSTANCES          = 'ENTITY_BOT_INSTANCES';
    const ENTITY_BOT_INSTANCES_DETAILS  = 'ENTITY_BOT_INSTANCES_DETAILS';
    const ENTITY_AWS_REGION             = 'ENTITY_AWS_REGION';
    const ENTITY_BOT                    = 'ENTITY_BOT';
    const ENTITY_PLATFORM               = 'ENTITY_PLATFORM';
    const ENTITY_SCHEDULING             = 'ENTITY_SCHEDULING';
    const ENTITY_CREDIT_USAGE           = 'ENTITY_CREDIT_USAGE';

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
            case self::ENTITY_BOT_INSTANCES_DETAILS:

//                $query->leftJoin('bot_instances_details', function ($join) {
//                    $join->on('bot_instances.id', '=', 'bot_instances_details.instance_id');
//                })
//                ->orderBy("bot_instances_details.{$sort['field']}", $order);

                break;
        }

        return $query;
    }

    public static function orderBot(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_PLATFORM:
                $query->leftJoin('platforms', function ($join) {
                    $join->on('bots.platform_id', '=', 'platforms.id');
                })
                ->orderBy("platforms.{$sort['field']}", $order)
                ->select('bots.*');
                break;
            case self::ENTITY_BOT:
                $query->orderBy("{$sort['field']}", $order);
                break;
        }

        return $query;
    }

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
        }

        return $query;
    }

    public static function orderCreditHistory(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_CREDIT_USAGE:
                $query->orderBy("{$sort['field']}", $order);
                break;
        }

        return $query;
    }

    public static function orderAwsRegion(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_AWS_REGION:
                $query->orderBy("{$sort['field']}", $order);
                break;
        }

        return $query;
    }

    public static function orderUser(Builder $query, array $sort, string $order): Builder
    {
        switch ($sort['entity']) {
            case self::ENTITY_USER:
                $query->orderBy("{$sort['field']}", $order);
                break;
            case self::ENTITY_ROLE:
                $query->leftJoin('roles', function ($join) {
                    $join->on('users.role_id', '=', 'roles.id');
                })
                    ->orderBy("roles.{$sort['field']}", $order)
                    ->select('users.*');
                break;
        }

        return $query;
    }
}
