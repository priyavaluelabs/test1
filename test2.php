/**
     * Add custom validations AFTER default validation.
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {

            // If any field already has errors, skip additional validation
            if ($validator->errors()->has('trainer_id')) {
                return;
            }
            
            // Fetch trainer from another database connection
            $trainer = UserPortal::on('mysql_portal')->find($this->trainer_id);

            // Validate trainer onboarding + Stripe connection
            if (
                !$trainer ||
                !$trainer->stripe_account_id ||
                !$trainer->is_onboarded
            ) {
                $validator->errors()->add(
                    'trainer_id',
                    __('klyp.nomergy::fod.trainer_not_in_stripe')
                );
                return;
            }

            // Validate that the customer ID belongs to this trainer
            if ($this->customer_id !== $trainer->stripe_customer_id) {
                $validator->errors()->add(
                    'customer_id',
                    __('klyp.nomergy::fod.stripe_unautorized_access_error')
                );
            }
        });
    }