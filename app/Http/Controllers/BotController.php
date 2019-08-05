<?php

namespace App\Http\Controllers;

use App\Platform;

class BotController extends Controller
{
    public function index($platformId = null)
    {
        $limit = empty($platformId) ? 5 : null;

        $platforms = Platform::hasBots($limit, $platformId, $status = 'active')
            ->paginate(5);

        return view('user.bots.index', compact('platforms'));
    }
}
