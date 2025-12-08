<?php 

namespace Klyp\Nomergy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
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
            'trainer_id'  => 'required|integer|exists:mysql_portal.users,id',
            'customer_id' => 'required|string',
            'limit'       => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages()
    {
        return [
            'trainer_id.required' => 'Trainer ID is required.',
            'trainer_id.exists'   => 'Trainer does not exist in the portal database.',
            'customer_id.required' => 'Customer ID is required.',
            'limit.integer'        => 'Limit must be an integer.',
            'limit.min'            => 'Limit must be at least :min.',
            'limit.max'            => 'Limit cannot be greater than :max.',
        ];
    }
}


public function mustBeUniqueEmail()
    {
        return function ($attribute, $value, $fail) {
            $user = User::where('email', $value)->first();

            // If the user is not found or the flex_user_id is the same as the one in the request, 
            // the request is for updating the user. Hence, the email is valid.
            if( !$user || $user->flex_user_id == $this->flex_user_id) {
                return;
            }

            $fail('The email has already been taken for flex user id ' . $user->flex_user_id);
        };
    }