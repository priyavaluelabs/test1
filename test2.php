<x-filament-panels::page>
    {{-- Payment tab section --}}
    <div class="filament-forms-card-component 
        bg-white dark:bg-gray-900 
        border border-gray-200 dark:border-gray-700 
        rounded-xl 
        w-[800px] h-[56px]
        flex items-center">
        <x-paymenttab />
    </div>

    {{-- Main catalog container --}}
    <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 w-[800px]">
        <div class="p-6 space-y-4">

            {{-- Header + Refresh button --}}
            <div class="flex items-center justify-between mb-4">
                <h1 class="font-sans font-bold text-[28px] text-[#444444]">
                    {{ __('stripe.product_catalog') }}
                </h1>

                <x-filament::button
                    wire:click="refreshProducts"
                    wire:loading.attr="disabled"
                    wire:target="refreshProducts"
                    class="!h-10 !px-4 !text-sm"
                >
                    {{ __('stripe.refresh_product') }}
                </x-filament::button>
            </div>

            {{-- Section title and description --}}
            <h2 class="font-sans font-bold text-[20px] leading-[36px] text-[#444444] mt-4">
                Session Packs
            </h2>
            <p class="font-sans font-semibold text-sm leading-[20px] text-black">
                {{ __('stripe.product_subheading') }}
            </p>

            {{-- Products list --}}
            @foreach ($products as $product)
                <div class="pt-4 flex flex-col space-y-4">

                    {{-- Name + Edit --}}
                    <div class="flex items-center justify-between w-full">
                        <span class="block font-sans font-bold text-[16px] leading-[24px] text-[#444444]">
                            {{ $product['name'] }}
                        </span>

                        <span
                            class="
                                font-sans font-semibold
                                text-[14px] leading-[20px] tracking-[0em]
                                text-[#C4161C]
                                no-underline cursor-pointer
                                flex items-center
                                gap-2
                            "
                            wire:click="openEditModal('{{ $product['id'] }}')"
                        >
                            <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="openEditModal('{{ $product['id'] }}')" />
                            {{ __('labels.edit') }}
                        </span>
                    </div>

                    {{-- Price --}}
                    <p class="font-sans font-semibold text-[16px] leading-[20px] text-[#444444]">
                        @if ($product['price'])
                            {{ __('stripe.price') }}: {{ strtoupper($product['currency']) }} {{ number_format($product['price'], 2) }}
                        @else
                            {{ __('stripe.price') }}: <span class="text-gray-500">{{ __('stripe.no_price') }}</span>
                        @endif
                    </p>

                    {{-- Description --}}
                    <div class="font-sans text-[14px] leading-[20px] text-[#444444]">
                        <span class="font-semibold">{{ __('labels.description') }}:</span><br>
                        {{ $product['description'] ?: __('stripe.no_description') }}
                    </div>

                    {{-- Active Status --}}
                    <p>
                        <span class="inline-block px-2 py-1 text-sm rounded
                            {{ $product['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $product['active'] ? __('stripe.active') : __('stripe.in_active')  }}
                        </span>
                    </p>

                </div>

                @unless ($loop->last)
                    <hr class="w-[750px] h-0 border border-[#EEEEEE] opacity-100" />
                @endunless
            @endforeach

        </div>
    </div>

    {{-- Edit Modal --}}
    <x-filament::modal id="edit-product" width="lg">
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full px-3">
                <h2 class="font-sans font-bold text-xl text-[#444]">
                    {{ $editForm['name'] }}
                </h2>
            </div>
        </x-slot>

        <hr class="h-0 border border-[#EEEEEE] opacity-100" />

        <div class="space-y-4 px-4">
            <div class="font-sans font-bold text-[16px] leading-[24px] tracking-[0%] text-[#444444]">
                {{ __('stripe.product_details') }}
            </div>
            {{-- Price --}}
            <div class="flex items-center space-x-2">
                <span class="block text-sm font-medium text-gray-700">{{ __('stripe.price') }}:</span>
                <span class="block text-sm font-medium text-gray-700">$</span>
                <input
                    type="number"
                    wire:model.defer="editForm.price"
                    placeholder="0.00"
                    class="
                        block w-[120px] rounded-lg border border-gray-300 bg-white px-3 py-2
                        text-sm font-sans text-gray-700 shadow-sm
                        placeholder-gray-400
                        focus:border-primary-500 focus:ring-1 focus:ring-primary-500 focus:outline-none
                    "
                >
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('labels.description') }}:</label>
                <textarea
                    wire:model.defer="editForm.description"
                    rows="3"
                    placeholder="Enter description..."
                    class="
                        block w-full rounded-lg border border-gray-300 bg-white px-3 py-2
                        text-sm font-sans text-gray-500 focus:border-primary-500 focus:ring-1
                        focus:ring-primary-500 focus:outline-none
                        @error('description') border-danger-600 ring-danger-600 @enderror
                    "
                ></textarea>
                @error('description')
                    <p class="fi-input-error-message">{{ $message }}</p>
                @enderror
            </div>

            {{-- Active toggle --}}
            <div class="flex items-center space-x-2">
                <span class="block text-sm font-medium text-gray-700">{{ __('stripe.active') }}:</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.defer="editForm.active" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 rounded-full peer-checked:bg-primary-500 dark:peer-checked:bg-primary-600 transition-colors"></div>
                    <span class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full peer-checked:translate-x-5 transition-transform"></span>
                </label>
            </div>
        </div>

        <x-slot name="footer">
            <x-filament::button
                wire:click="save"
                class="
                    !w-[466px]
                    !h-[44px]
                    !border-[1px]
                    !border-[#C4161C]
                    !rounded-[8px]
                    flex items-center justify-center
                "
            >
                {{ __('labels.save') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
