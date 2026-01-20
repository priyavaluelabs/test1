<div class="rounded-xl bg-white border border-gray-200 mt-6">
    <div class="p-6 space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-bold">Promo Codes</h2>
            <x-filament::button wire:click="createPromoCode">
                + Add New
            </x-filament::button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b text-gray-500">
                    <tr>
                        <th class="py-2 text-left">Code</th>
                        <th class="py-2 text-left">Status</th>
                        <th class="py-2 text-left">Redemptions</th>
                        <th class="py-2 text-left">Restrictions</th>
                        <th class="py-2 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y">
                    @forelse($promoCodes as $promo)
                        <tr>
                            <td class="py-3 font-mono">{{ $promo['code'] }}</td>

                            {{-- STATUS --}}
                            <td>
                                @if($promo['active'])
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-600">
                                        Archived
                                    </span>
                                @endif
                            </td>

                            {{-- REDEMPTIONS --}}
                            <td>
                                {{ $promo['times_redeemed'] }}
                                /
                                {{ $promo['max_redemptions'] ?? '∞' }}
                            </td>

                            {{-- RESTRICTIONS --}}
                            <td class="text-gray-600">
                                {{ $promo['restrictions'] ?? '—' }}
                            </td>

                            {{-- ACTIONS --}}
                            <td class="text-right space-x-2">
                                @if($promo['active'])
                                    <x-filament::button
                                        size="sm"
                                        color="warning"
                                        wire:click="archivePromoCode('{{ $promo['id'] }}')">
                                        Archive
                                    </x-filament::button>
                                @endif

                                <x-filament::button
                                    size="sm"
                                    color="danger"
                                    wire:click="deletePromoCode('{{ $promo['id'] }}')">
                                    Delete
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-400">
                                No promo codes created yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>



====



public array $promoCodes = [];

protected function loadPromoCodes(): void
{
    if (! $this->couponId) {
        $this->promoCodes = [];
        return;
    }

    $promos = $this->stripeClient()->promotionCodes->all([
        'coupon' => $this->couponId,
        'limit' => 100,
    ]);

    $this->promoCodes = collect($promos->data)->map(function ($promo) {
        return [
            'id' => $promo->id,
            'code' => $promo->code,
            'active' => $promo->active,
            'times_redeemed' => $promo->times_redeemed,
            'max_redemptions' => $promo->max_redemptions,
            'restrictions' => isset($promo->restrictions['redeem_by'])
                ? 'Valid until ' . date('M d, Y', $promo->restrictions['redeem_by'])
                : null,
        ];
    })->toArray();
}
