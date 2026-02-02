<?php

return [
    'payments' => 'Payments',
    'personal_training_onboarding' => 'Personal Training - Onboarding',
    'onboarding' => 'Onboarding',
    'transactions' => 'Transactions',
    'products' => 'Products',
    'payouts' => 'Payouts',
    'invoicing' => 'Invoicing',
    'settings' => 'Settings',
    'discounts' => 'Discounts',
    'settings'  => 'Settings',
    'not_provided' => 'Not Provided',
    'no_payment_history' => 'No Payment History Yet',
    'no_payment_history_description' => 'You have not received or recorded any payments so far.',
    'trainer_terms_updated' => 'Trainer terms and conditions updated successfully.',
    'trainer_terms_and_condition_title' => 'Trainer Terms and Conditions',
    'trainer_terms_and_condition_text1' => 'Your terms and conditions will be displayed for clients when',
    'trainer_terms_and_condition_text2' => 'they purchase your products.',
    'tax_settings' => 'Tax Settings',
    'tax_registrations' => 'Tax Registrations',
    'invalid_stripe_key' => 'Please try again later or reach out to support.',
    'stripe_configuration_error' => 'Stripe Configuration Error',
    'webhook_not_configured' => 'Webhook secret not configured',
    'missing_signature_header' => 'Missing signature header',

    //Stripe Onboarding
    'onboarding_completed' => 'Onboarding Completed!',
    'onboarding_completed_text' => 'Your Stripe account is active and ready to use.',

    //Stripe Product
    'stripe_product' => 'Stripe Product',
    'product_catalog' => 'Product Catalog',
    'product_subheading' => 'Select products below to make them available for clients to purchase from you. Ensure you have the correct taxes configured in your settings. Managing these settings is solely your responsibility.',
    'product_details' => 'Product Details',
    'product_name' => 'Product Name',
    'price' => 'Price',
    'no_price' => '(not set)',
    'visibility' => 'Visibility',
    'active' => 'Active',
    'in_active' => 'Inactive',
    'no_description' => 'No description available' ,
    'active_products' => 'Active Product',
    'product_update_success' => 'Product updated successfully',
    'product_id' => 'Product ID',
    'no_stripe_product'=> 'No Stripe Products Found',
    'no_stripe_product_description' => 'There are currently no Stripe products to display.',
    'error_update_product' => 'Error updating product',
    'error_no_field_update' => 'Please update at least one field before submitting.',
    'refresh' => 'Refresh',
    'validation_failed' => 'Validation Failed',
    'validation_terms_condition_required' => 'Terms and condition is required',
    'placeholder' => 'Placeholder',


    // Punch card history
    'billing' => 'Billing',
    'restore' => 'Restore',
    'restore_most_recent_session' => 'Restore most recent session',
    'restore_most_recent_session_confirm' => 'Are you sure you want to restore the most recently checked-off session? This action cannot be undone.',
    'check_off' => 'Check off',
    'check_off_session' => 'Check off session',
    'purchased' => 'Purchased',
    'session_available' => 'Sessions available',
    'history' => 'History',
    'no_billing_data' => 'No billing records available.',
    'no_session_left' => 'No sessions left',
    'session_checked_off' => 'Session checked off',
    'punch_card_not_found' => 'Punch card not found',
    'session_restored' => 'Session restored',
    'most_recent_session_restored' => 'Most recent session restored',
    'no_sessions_to_restore' => 'No sessions to restore',


    // Discount
    'name_product' => 'Name & Product',
    'details' => 'Details',
    'promo_codes' => 'Promo Codes',
    'status_active' => 'Active',
    'status_inactive' => 'Inactive',
    'status_expired' => 'Expired',
    'first_purchase_only' => '1st purchase only',
    'no_records_found' => 'No records found.',
    'create_new' => 'Create New',
    'create_discount_promo' => 'Create Discount & Promo Code',
    'discount_details' => 'Discount Details',
    'edit_discount_promo' => 'Edit Discount & Promo Code',
    'coupon_name' => 'Name (appears on receipts)',
    'products' => 'Products',
    'discount' => 'Discount',
    'coupon_description' => 'Description (Optional)',
    'not_provided' => '—',
    'promo_codes' => 'Promo Codes',
    'add_new' => '+ Add New',
    'code' => 'Code',
    'status' => 'Status',
    'redemptions' => 'Redemptions',
    'restrictions' => 'Restrictions',
    'archive' => 'Archive',
    'unarchive' => 'Unarchive',
    'no_promo_codes' => 'No promo codes created yet',
    'actions' => 'Actions',

    // Actions
    'delete' => 'Delete',
    'add_promo_code' => 'Add Promo Code',
    'add_promo_code_heading' => 'Add a Promo Code',
    'finish' => 'Finish',

    // Coupon
    'coupon_create' => 'Coupon created successfully',
    'coupon_create_failed' => 'Failed to create coupon',
    'coupon_updated' => 'Coupon updated successfully',
    'coupon_updated_failed' => 'Failed to update coupon',
    'coupon_deleted' => 'Coupon deleted',
    'coupon_delete_failed' => 'Failed to delete coupon',

    // Promo code status
    'promo_code_created' => 'Promo code created',
    'promo_code_failed' => 'Failed to create promo code',
    'promo_code_archive_and_unarchive_failed' => 'Failed to archive/unarchive promo code',
    'promo_archived' => 'Promo code archived',
    'promo_unarchived' => 'Promo code unarchived',

    // Form labels
    'code' => 'Code',
    'code_placeholder' => 'e.g. SPRING10',
    'code_helper' => 'Customers will enter this code at checkout',

    'expire_date' => 'Expire Date (Optional)',
    'restrict_customer' => 'Restrict to Customer (Optional)',
    'all_customers' => 'All customers',

    'total_limit' => 'Total Limit',
    'limit_per_customer' => 'Limit Per Customer',
    'unlimited' => 'Unlimited',

    'limit_first_purchase' => 'Limit to first purchase',

    'no_name' => 'No Name',
    'no_email' => 'No Email',

    'percentage' => 'Percentage',
    'fixed_amount' => 'Fixed Amount',

    'discount_type' => 'Discount Type',
    'all_products'  => 'All Products',
    'value' => 'Value',

    'off' => 'off',
    'valid_range' => 'Valid :from - :to',
    'valid_from'  => 'Valid from :from',
];

=======

<?php

namespace App\Filament\Enum;

enum StripeAccountStatus: string
{
    case PENDING         = 'pending';
    case RESTRICTED      = 'restricted';
    case RESTRICTED_SOON = 'restricted_soon';
    case ENABLED         = 'enabled';
    case COMPLETE        = 'complete';
    case REJECTED        = 'rejected';
    case UNKNOWN         = 'unknown';

    public function title(): string
    {
        return match ($this) {
            self::PENDING         => 'Onboarding in Progress',
            self::RESTRICTED      => 'Account Restricted',
            self::RESTRICTED_SOON => 'Action Required Soon',
            self::ENABLED         => 'Account Enabled',
            self::COMPLETE        => 'Onboarding Completed!',
            self::REJECTED        => 'Account Not Approved',
            self::UNKNOWN         => 'Account Status Unknown',
        };
    }

    public function message(): string
    {
        return match ($this) {
            self::PENDING =>
                'Your account details are under review. Please complete the pending verification steps to continue.',

            self::RESTRICTED =>
                'Your Stripe account has some pending requirements. Payouts and charges are temporarily unavailable until these are resolved.',

            self::RESTRICTED_SOON =>
                'Additional information is needed to keep your Stripe account active. Please complete the required steps before the deadline.',

            self::ENABLED =>
                'Your Stripe account is active. Some additional details may be required later.',

            self::COMPLETE =>
                'Your Stripe account is active and ready to use.',

            self::REJECTED =>
                'Your Stripe account could not be approved. Please contact support for more information.',

            self::UNKNOWN =>
                'We are unable to determine your Stripe account status at the moment.',
        };
    }

    /**
     * Tailwind UI classes
     */
    public function uiColors(): array
    {
        return match ($this) {
            self::COMPLETE => [
                'bg'     => 'bg-green-100',
                'border' => 'border-green-300',
                'text'   => 'text-green-800',
            ],

            self::ENABLED => [
                'bg'     => 'bg-blue-100',
                'border' => 'border-blue-300',
                'text'   => 'text-blue-800',
            ],

            self::PENDING,
            self::RESTRICTED_SOON => [
                'bg'     => 'bg-yellow-100',
                'border' => 'border-yellow-300',
                'text'   => 'text-yellow-800',
            ],

            self::RESTRICTED,
            self::REJECTED,
            self::UNKNOWN => [
                'bg'     => 'bg-red-100',
                'border' => 'border-red-300',
                'text'   => 'text-red-800',
            ],
        };
    }
}

