<x-filament-panels::page>
    <div class="filament-tables-container rounded-xl border border-gray-300 bg-white shadow-sm">
        <x-payment-tab />
    </div>

    @if (! $stripeAvailable)
        <x-stripe.configuration-error :stripeErrorMessage="$stripeErrorMessage"/>
    @else
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700" wire:ignore>
            <div class="p-6 space-y-4">
                <h1 class="font-bold text-[28px] text-gray-700">
                    {{ __('stripe.discounts') }}
                </h1>

                <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Name & Product</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Details</th>
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Promo Codes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($records as $record)
                            <tr>
                                <!-- Name & Product -->
                                <td class="px-4 py-2">
                                    <div class="font-medium">{{ $record['coupon_name'] }}</div>
                                    <ul class="ml-4 list-disc text-sm text-gray-500">
                                        @foreach($record['products'] as $product)
                                            <li>{{ $product }}</li>
                                        @endforeach
                                    </ul>
                                </td>

                                <!-- Details -->
                                <td class="px-4 py-2">
                                    <div>{{ $record['discount'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $record['description'] }}</div>
                                </td>

                                <!-- Promo Codes -->
                                <td class="px-4 py-2">
                                    @foreach($record['promotion_codes'] as $promo)
                                        <div class="mb-2 p-2">
                                            {{-- Promo code and status --}}
                                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">
                                                    {{ $promo['code'] }}
                                                </span>
                                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded
                                                    {{ $promo['status'] === 'Active'
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-orange-100 text-orange-700' }}">
                                                    {{ $promo['status'] }}
                                                </span>
                                            </div>

                                            {{-- Validity --}}
                                            <div class="text-xs text-gray-500 mb-1">
                                                {{ $promo['validity'] }}
                                            </div>

                                            {{-- First purchase only --}}
                                            @if($promo['is_first'])
                                                <div class="inline-block px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-700 mb-1">
                                                    1st purchase only
                                                </div>
                                            @endif

                                            {{-- Usage --}}
                                            @if($promo['usage'])
                                                <div class="inline-block px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-600">
                                                    {{ $promo['usage'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-center text-gray-500">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Create New Button -->
                <div class="pt-4">
                    <x-filament::button class="!w-[300px] !h-[44px]">
                        Create New
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
