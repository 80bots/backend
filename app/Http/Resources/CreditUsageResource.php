<?php

namespace App\Http\Resources;

use App\CreditUsage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CreditUsageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $credits = ($this->action === CreditUsage::ACTION_ADDED) ? ("+" . $this->credits ?? 0) : ("-" . $this->credits ?? 0);

        $data = [
            'id'        => $this->id ?? '-',
            'credits'   => $credits,
            'total'     => $this->total ?? 0,
            'action'    => ucfirst($this->action ?? ''),
            'subject'   => $this->subject ?? '-',
            'date'      => $this->created_at->format('Y-m-d H:i:s')
        ];

        if (Auth::check() && Auth::user()->isAdmin()) {
            $data = array_merge($data, [
                'user' => $this->user->email ?? ''
            ]);
        }

        return $data;
    }
}
