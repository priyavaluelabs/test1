$this->promoCodes = collect($this->promoCodes)
    ->push([
        'id' => $promo->id,
        'code' => $promo->code,
        'active' => $promo->active,
    ])
    ->toArray();
