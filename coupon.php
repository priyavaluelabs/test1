    <div class="rounded-xl bg-white border border-gray-200">
        <div class="p-6 space-y-6">
            <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-gray-700">
                ←
            </a>
            <h2 class="text-xl font-bold">
                {{ $isEdit ? 'Edit Discount & Promo Code' : 'Create Discount & Promo Code' }}
            </h2>
