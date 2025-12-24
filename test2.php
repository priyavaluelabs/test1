<?php

namespace Klyp\Nomergy\Services\Stripe;

use Carbon\Carbon;
use Klyp\Nomergy\Models\UserPortal;

class PTBillingCustomerPunchCardService
{
    public function getPunchCardInfoWithHistory(object $user): array
    {
        $punchCards = $user->punchCard()
            ->with(['histories', 'trainer'])
            ->get();

        return $punchCards->map(function ($punchCard) {

            return [
                'product_name'  => $punchCard->product_name,
                'trainer_name'  => optional($punchCard->trainer)->first_name . ' ' .
                                   optional($punchCard->trainer)->last_name,
                'purchased_at'  => Carbon::parse($punchCard->purchased_at)->format('F d'),
                'total_session' => $punchCard->total_session,
                'used_session'  => $punchCard->used_session,
                'history'       => $this->formatHistory($punchCard->histories),
            ];

        })->values()->toArray();
    }

    private function formatHistory($histories): array
    {
        return $histories->map(function ($history) {
            $date = Carbon::parse($history->date_of_session);

            return [
                'date'   => $date->format('d M Y'),
                'time'   => $date->format('h:i A'),
                'action' => $history->action,
            ];
        })->toArray();
    }
}
