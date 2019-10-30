<?php

namespace App;

class Notification extends BaseModel
{
    const STATUS_QUEUED         = 'queued';
    const STATUS_SENT           = 'sent';
    const STATUS_NOT_REQUIRED   = 'not-required';

    protected $table = 'notifications';
}
