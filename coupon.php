<!-- Actions -->
<td class="px-4 py-2">
    <x-filament::button
        icon="heroicon-o-pencil"
        size="sm"
        color="primary"
        tag="a"
        href="{{ route('coupons.edit', ['coupon' => $record['id']]) }}"
        class="!px-2 !py-1">
        {{-- optional: you can leave text empty to show only icon --}}
    </x-filament::button>
</td>


 <!-- Create New Button -->
                <div class="pt-4">
                    <x-filament::button
                        class="!w-[300px] !h-[44px]"
                        tag="a"
                        href="{{ route('coupons.create') }}">
                        Create New
                    </x-filament::button>
                </div>
