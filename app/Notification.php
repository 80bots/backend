<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    const STATUS_QUEUED         = 'queued';
    const STATUS_SENT           = 'sent';
    const STATUS_NOT_REQUIRED   = 'not-required';

    protected $table = 'notifications';
}
