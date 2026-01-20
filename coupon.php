<div class="rounded-xl border border-gray-200 bg-white mt-6">
    {{-- HEADER (TITLE + ADD BUTTON) --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">
            Promo Codes
        </h2>

        <x-filament::button
            size="sm"
            color="danger"
            wire:click="createPromoCode">
            + Add New
        </x-filament::button>
    </div>

    {{-- TABLE --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr class="text-gray-600">
                    <th class="px-4 py-3 text-left font-medium">Code</th>
                    <th class="px-4 py-3 text-left font-medium">Status</th>
                    <th class="px-4 py-3 text-left font-medium">Redemptions</th>
                    <th class="px-4 py-3 text-left font-medium">Restrictions</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @forelse($promoCodes as $promo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono font-medium">
                            {{ $promo['code'] }}
                        </td>

                        <td class="px-4 py-3">
                            @if($promo['active'])
                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                                    Archived
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            {{ $promo['times_redeemed'] }}
                            <span class="text-gray-400">/</span>
                            {{ $promo['max_redemptions'] ?? '∞' }}
                        </td>

                        <td class="px-4 py-3 text-gray-600">
                            {{ $promo['restrictions'] ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-right space-x-2">
                            @if($promo['active'])
                                <x-filament::button
                                    size="sm"
                                    outlined
                                    color="warning"
                                    wire:click="archivePromoCode('{{ $promo['id'] }}')">
                                    Archive
                                </x-filament::button>
                            @endif

                            <x-filament::button
                                size="sm"
                                outlined
                                color="danger"
                                wire:click="deletePromoCode('{{ $promo['id'] }}')">
                                Delete
                            </x-filament::button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                            No promo codes created yet
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
