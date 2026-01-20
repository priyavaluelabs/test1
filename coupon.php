<x-filament-panels::page>

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-gray-700">
            ←
        </a>
        <h1 class="text-2xl font-semibold">
            {{ $isEdit ? 'Discount & Promo Code' : 'Create Discount & Promo Code' }}
        </h1>
    </div>

    <div class="rounded-xl bg-white border border-gray-200">
        <div class="p-6 space-y-6">

            {{-- ===================== --}}
            {{-- VIEW MODE (Stripe UI) --}}
            {{-- ===================== --}}
            @if ($isEdit && ! $isEditing)

                <h2 class="text-lg font-semibold flex items-center justify-between">
                    Discount Details
                    {{-- Edit icon --}}
                    <button wire:click="startEditing" class="text-gray-400 hover:text-gray-600">
                        <!-- Heroicon pencil -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 4h2M12 2v2m6.364 2.636l-2.828 2.828m-1.414-1.414L16.95 5.05m-7.778 7.778l-2.828 2.828m1.414 1.414L5.05 16.95M20 12h-2M12 20v-2" />
                        </svg>
                    </button>
                </h2>

                <div class="space-y-5">

                    {{-- Name --}}
                    <div>
                        <p class="text-sm text-gray-500">
                            Name (appears on receipts)
                        </p>
                        <p class="text-base font-medium">
                            {{ $formData['name'] }}
                        </p>
                    </div>

                    {{-- Products --}}
                    <div>
                        <p class="text-sm text-gray-500">
                            Product(s)
                        </p>
                        <p class="text-base font-medium">
                            All Products
                        </p>
                    </div>

                    {{-- Discount Type --}}
                    <div>
                        <p class="text-sm text-gray-500">
                            Discount Type
                        </p>
                        <p class="text-base font-medium">
                            {{ $formData['discount_type'] === 'percentage'
                                ? $formData['value'].'% Off'
                                : '$'.$formData['value'].' Off' }}
                        </p>
                    </div>

                    {{-- Description --}}
                    <div>
                        <p class="text-sm text-gray-500">
                            Description (optional)
                        </p>
                        <p class="text-base text-gray-700">
                            {{ $formData['description'] ?? '—' }}
                        </p>
                    </div>

                </div>

                {{-- Delete button --}}
                <div class="flex gap-3 pt-6">
                    <x-filament::button color="danger">
                        Delete
                    </x-filament::button>
                </div>

            @endif

            {{-- ===================== --}}
            {{-- EDIT / CREATE MODE --}}
            {{-- ===================== --}}
            @if ($isEditing)

                {{ $this->form }}

                <div class="flex gap-3 pt-6">
                    <x-filament::button wire:click="save">
                        Save
                    </x-filament::button>

                    @if ($isEdit)
                        <x-filament::button color="secondary" wire:click="cancelEditing">
                            Cancel
                        </x-filament::button>
                    @endif
                </div>

            @endif

        </div>
    </div>

</x-filament-panels::page>
