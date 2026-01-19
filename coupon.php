protected function formatDiscount($coupon): string
{
    // Percentage discount
    if (!is_null($coupon->percent_off)) {
        return "{$coupon->percent_off}% off";
    }

    // Fixed amount discount
    if (!is_null($coupon->amount_off)) {
        $currency = strtoupper($coupon->currency ?? 'INR');
        $symbol = $this->currencySymbol($currency);

        return $symbol . number_format($coupon->amount_off / 100, 2) . " off";
    }

    return '—';
}
