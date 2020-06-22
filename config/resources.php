<?php

return [
    'region' => [
        'region' => 'name',
    ],
    'instance' => [
        'id'                => 'id',
        'uptime'            => 'up_time',
        'total_up_time'     => 'total_up_time',
        'cron_up_time'      => 'cron_up_time',
        'status'            => 'aws_status'
    ],
    'details' => [
        'name'              => 'tag_name',
        'launched_by'       => 'tag_user_email',
        'launched_at'       => 'start_time',
        'instance_id'       => 'aws_instance_id',
        'tag_user_email'    => 'tag_user_email',
        'ip'                => 'aws_public_ip',
        'pem'               => 'aws_pem_file_path'
    ],
    'bot' => [
        'bot_name'      => 'name',
        'parameters'    => 'parameters',
    ]
];
