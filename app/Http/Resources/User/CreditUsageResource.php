<?php

namespace App\Http\Resources\User;

use App\CreditUsage;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditUsageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $credits = ($this->action === CreditUsage::ACTION_ADDED) ? ('+' . $this->credit ?? 0) : ($this->credit ?? 0);

        return [
            'id'        => $this->id ?? '-',
            'credits'   => $credits,
            'total'     => $this->total ?? 0,
            'action'    => ucfirst($this->action ?? ''),
            'subject'   => $this->subject ?? '-',
            'date'      => $this->created_at->format('Y-m-d H:i:s')
        ];
    }
}