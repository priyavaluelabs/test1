<h3 class="font-sans font-bold text-xl leading-7 text-gray-950 dark:text-white mb-4">
    Billing
</h3>

<div class="w-full border-t border-gray-200 dark:border-gray-700"></div>

<div class="pt-4 space-y-4">
    @forelse ($billings as $billing)
        <div class="space-y-2">

            <div class="text-sm font-semibold text-gray-900 dark:text-white">
                {{ $billing->product_name }}
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400">
                Purchased {{ \Carbon\Carbon::parse($billing->purchased_at)->format('F d') }}
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400">
                Sessions available
                <span class="font-medium text-gray-700 dark:text-gray-300">
                    {{ $billing->total_session - $billing->used_session }}
                    / {{ $billing->total_session }}
                </span>
            </div>

            <div class="flex items-center gap-1 mt-1">
                @for ($i = 1; $i <= $billing->total_session; $i++)
                    @if ($i <= $billing->used_session)
                        <span class="w-3.5 h-3.5 rounded-full bg-danger-600 inline-block"></span>
                    @else
                        <span class="w-3.5 h-3.5 rounded-full border border-danger-600"></span>
                    @endif
                @endfor
            </div>

            <div class="flex items-center gap-2 pt-3 pb-3">
                <x-filament::button
                    color="primary"
                    size="sm"
                    wire:click="openCheckOffSessionModal({{ $billing->id }})"
                >
                    Check off session
                </x-filament::button>

                <x-filament::button
                    color="gray"
                    size="sm"
                    wire:click="openRestoreSessionModal({{ $billing->id }})"
                >
                    Restore most recent session
                </x-filament::button>
            </div>

            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
        </div>
    @empty
        <div class="text-sm text-gray-500 dark:text-gray-400">
            No billing records available.
        </div>
    @endforelse
</div>

{{-- CHECK OFF SESSION MODAL --}}
<x-filament::modal id="check-off-session-modal" width="sm">
    <x-slot name="heading">Check off session</x-slot>

    <div class="space-y-3">
        <x-filament::input.wrapper>
            <x-filament::input
                type="date"
                wire:model.defer="checkOffDate"
                max="{{ now()->toDateString() }}"
            />

            @error('checkOffDate')
                <p class="mt-1 text-sm text-danger-600">
                    {{ $message }}
                </p>
            @enderror
        </x-filament::input.wrapper>
    </div>

    <x-slot name="footer">
        <div class="flex justify-end gap-2">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'check-off-session-modal' })"
            >
                Cancel
            </x-filament::button>

            <x-filament::button
                color="primary"
                wire:click="submitCheckOffSession"
                wire:loading.attr="disabled"
            >
                Check off
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>

{{-- RESTORE SESSION MODAL --}}
<x-filament::modal id="restore-session-modal" width="sm">
    <x-slot name="heading">Restore most recent session</x-slot>

    <div class="text-sm text-gray-700 dark:text-gray-300">
        Are you sure you want to restore the most recently checked-off session?
        This action cannot be undone.
    </div>

    <x-slot name="footer">
        <div class="flex justify-end gap-2">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'restore-session-modal' })"
            >
                Cancel
            </x-filament::button>

            <x-filament::button
                color="danger"
                wire:click="confirmRestoreSession"
            >
                Restore
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>


===


<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public ?int $selectedBillingId = null;
    public ?string $checkOffDate = null;

    /* ---------------- OPEN MODALS ---------------- */

    public function openCheckOffSessionModal(int $billingId): void
    {
        $this->resetErrorBag();
        $this->checkOffDate = null;
        $this->selectedBillingId = $billingId;

        $this->dispatch('open-modal', id: 'check-off-session-modal');
    }

    public function openRestoreSessionModal(int $billingId): void
    {
        $this->selectedBillingId = $billingId;

        $this->dispatch('open-modal', id: 'restore-session-modal');
    }

    /* ---------------- CHECK OFF SESSION ---------------- */

    public function submitCheckOffSession(): void
    {
        $validator = Validator::make(
            ['checkOffDate' => $this->checkOffDate],
            [
                'checkOffDate' => ['required', 'date', 'before_or_equal:today'],
            ],
            [
                'checkOffDate.required' => 'Please select a date.',
                'checkOffDate.date' => 'Invalid date selected.',
                'checkOffDate.before_or_equal' => 'Date cannot be in the future.',
            ]
        );

        if ($validator->fails()) {
            $this->setErrorBag($validator->errors());
            return;
        }

        $punchCard = $this->record
            ->trainerBillings()
            ->whereKey($this->selectedBillingId)
            ->first();

        if (! $punchCard) {
            Notification::make()
                ->title('Punch card not found')
                ->danger()
                ->send();
            return;
        }

        if ($punchCard->used_session >= $punchCard->total_session) {
            Notification::make()
                ->title('No sessions left')
                ->warning()
                ->send();
            return;
        }

        $punchCard->increment('used_session');

        $punchCard->histories()->create([
            'action' => 'Session checked off',
            'date_of_session' => Carbon::parse($this->checkOffDate)
                ->setTimeFromTimeString(now()->format('H:i:s')),
        ]);

        Notification::make()
            ->title('Session checked off')
            ->success()
            ->send();

        $this->reset('checkOffDate', 'selectedBillingId');
        $this->resetErrorBag();

        $this->dispatch('close-modal', id: 'check-off-session-modal');
    }

    /* ---------------- RESTORE SESSION ---------------- */

    public function confirmRestoreSession(): void
    {
        $punchCard = $this->record
            ->trainerBillings()
            ->whereKey($this->selectedBillingId)
            ->first();

        if (! $punchCard || $punchCard->used_session <= 0) {
            Notification::make()
                ->title('No sessions to restore')
                ->warning()
                ->send();
            return;
        }

        $punchCard->decrement('used_session');

        $punchCard->histories()->create([
            'action' => 'Session restored',
            'date_of_session' => now(),
        ]);

        Notification::make()
            ->title('Most recent session restored')
            ->success()
            ->send();

        $this->reset('selectedBillingId');

        $this->dispatch('close-modal', id: 'restore-session-modal');
    }
}
