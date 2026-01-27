@php
    $alwaysEnabled = $getPaymentTab['always_enabled'] ?? false;
    $disabled = ! $isGlofoxVerified && ! $alwaysEnabled;
@endphp
