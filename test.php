if ($trainerId) {
    $trainer = UserPortal::find($trainerId);

    if ($trainer) {
        $corporatePartner = $trainer->getCorporatePartnerForFlexUser($trainer);
        if ($corporatePartner && isset($corporatePartner['region'])) {
            $region = $corporatePartner['region'];
        }
    }
} else {
    $user = $request->user();
    $region = $user->profile->partner->region;
}