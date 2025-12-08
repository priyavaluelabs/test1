<?php 

namespace Klyp\Nomergy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Klyp\Nomergy\Models\UserPortal;

class PTBillingStripePaymentHistoryByCustomerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'trainer_id'  => ['required', 'integer', $this->validateTrainerExist(), $this->matchTrainerCustomer()],
            'customer_id' => ['required', 'string'],
            'limit'       => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages()
    {
        return [
            'trainer_id.required' => 'Trainer ID is required.',
            'customer_id.required' => 'Customer ID is required.',
            'limit.integer'        => 'Limit must be an integer.',
            'limit.min'            => 'Limit must be at least :min.',
            'limit.max'            => 'Limit cannot be greater than :max.',
        ];
    }

    public function validateTrainerExist()
    {
        return function ($attribute, $value, $fail) {
            $trainer = UserPortal::where('id', $value)->first();
       
            if (!$trainer ||
                !$trainer->stripe_account_id ||
                !$trainer->is_onboarded
            ) {
               return $fail(__('klyp.nomergy::fod.trainer_not_in_stripe'));
            }
        };
    }

    public function matchTrainerCustomer()
    {
        return function ($attribute, $value, $fail) {
            $trainer = UserPortal::where('id', $value)->first();
       
            if ($this->customer_id !== $trainer->stripe_customer_id) {
               return $fail(__('klyp.nomergy::fod.stripe_unautorized_access_error'));
            }
        };
    }
}
