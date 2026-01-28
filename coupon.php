<div {{ $attributes->class(['rounded-t-lg']) }}>
    <ul 
        class="
            flex 
            flex-wrap 
            justify-center 
            -mb-px 
            text-sm 
            font-medium 
            text-center 
            text-gray-500 
            dark:text-gray-400
            space-x-2
            sm:space-x-3
            md:space-x-4
            xl:space-x-6
        "
        x-bind:class="$store.sidebar.isOpen ? 'xl:space-x-4' : 'xl:space-x-6'"
    >
        @foreach($getPaymentTabs as $getPaymentTab)
            <li>
                @php
                    $alwaysEnabled = $getPaymentTab['always_enabled'] ?? false;
                    $disabled = ! $isGlofoxVerified && ! $alwaysEnabled;
                @endphp

                <a 
                    @if(! $disabled)
                        href="{{ url($getPaymentTab['path']) }}"
                    @endif
                    class="
                        inline-flex 
                        items-center 
                        justify-center 
                        p-4 
                        border-b-2 
                        rounded-t-lg
                        {{ $disabled ? 'opacity-40 cursor-not-allowed pointer-events-none' : '' }}
                       @if($isActive($getPaymentTab['path']) && ! $disabled)
                            border-primary-600
                            dark:border-primary-500 
                            dark:text-primary-500 
                            text-primary-600
                        @else
                            border-transparent 
                            hover:text-gray-600 
                            hover:border-gray-300
                            dark:hover:text-gray-300 
                            group
                        @endif
                    "
                >
                    {{ $getPaymentTab['name'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
