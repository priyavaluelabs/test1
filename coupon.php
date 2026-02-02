$account->details_submitted === true &&
        empty($account->requirements->currently_due ?? []) &&
        empty($account->requirements->past_due ?? []);
