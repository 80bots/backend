<?php

namespace App\Services;

use GrahamCampbell\GitHub\Facades\GitHub as GitHubApi;

class GitHub
{

    public static function me()
    {
        return GitHubApi::me();
    }

    public static function issues()
    {
        return GitHubApi::issues()->all(config('github.organization'), config('github.repo'),
            ['state' => 'open']);
    }

    /**
     * @param $title
     * @param $body
     * @return array
     */
    public static function createIssue($title, $body)
    {
        return GitHubApi::issues()->create(config('github.organization'), config('github.repo'),
            [
                'title' => $title,
                'body' => $body
            ]
        );
    }
}
