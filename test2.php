<?php

namespace Klyp\Nomergy\Http\Controllers\Stripe;

use Klyp\Nomergy\Http\Controllers\ApiController;
use Klyp\Nomergy\Services\Stripe\PTBillingCustomerPunchCardService;
use Symfony\Component\HttpFoundation\Response;

class ApiPTBillingCustomerPunchCardController extends ApiController
{
    /**
     *  PT billing stripe customer service instance for handling Stripe API interactions.
     *
     * @var customerPunchCardServices
     */
    protected $customerPunchCardService;

    /**
     * Constructor to inject the customer punch card service.
     *
     * @param PTBillingCustomerPunchCardService $customerPunchCardService The service used to get puch card customer wise.
     *
     */
    public function __construct(
        PTBillingCustomerPunchCardService $customerPunchCardService
    ) {
        $this->customerPunchCardService = $customerPunchCardService;
    }

    /**
     * Retrieve a list of punch card for a given customer.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function getPunchCardInfoWithHistoryByCustomer()
    {
        $user = parent::getAuth();
        
        try {
            $punchCardData = $this->customerPunchCardService->getPunchCardInfoWithHistory($user);

            return parent::respond([
                'status' => 'success',
                'punch_card' => $punchCardData
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return parent::respondError(
                __('klyp.nomergy::fod.no_punch_card_history'),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}



================


<?php

namespace Klyp\Nomergy\Services\Stripe;

use Klyp\Nomergy\Models\PTBillingUserPunchCard;
use Klyp\Nomergy\Models\UserPortal;

class PTBillingCustomerPunchCardService
{
    /**
     * Retrieve a list of punch card information for a given customer.
     *
     * @param object $user
     * 
     */
    public function getPunchCardInfoWithHistory(object $user)
    {
        $puchCards = $user->punchCard()->with('histories')->get();
        $result = [];
        foreach ($puchCards as $puchCard) {
            $trainer = UserPortal::find($puchCard->trainer_id);

            $historyList = [];
            foreach ($puchCard->histories as $history) {
                $date = \Carbon\Carbon::parse($history->date_of_session);
                $historyList[] = [
                    'date' => $date->format('d M Y'),
                    'time' => $date->format('h:i A'),
                    'action' => $history->action,
                ];
            }

            $result[] = [
                'product_name' => $puchCard->product_name,
                'trainer_name' => $trainer->first_name . ' ' . $trainer->last_name,
                'purchased_at' => \Carbon\Carbon::parse($puchCard->purchased_at)->format('F d'),
                'total_session' => $puchCard->total_session,
                'used_session' => $puchCard->used_session,
                'history' => $historyList,
            ];
        }

        return $result;
    }
}
