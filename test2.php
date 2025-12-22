@foreach ($billings as $billing)
<div class="space-y-2">
    <!-- Billing Info -->
    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $billing->product_name }}</div>
    <div class="text-xs text-gray-500 dark:text-gray-400">
        Purchased {{ \Carbon\Carbon::parse($billing->purchased_at)->format('F d') }}
    </div>
    <div class="text-xs text-gray-500 dark:text-gray-400">
        Sessions available
        <span class="font-medium text-gray-700 dark:text-gray-300">
            {{ $billing->total_session - $billing->used_session }}/{{ $billing->total_session }}
        </span>
    </div>

    <!-- Session Dots -->
    <div class="flex items-center gap-1 mt-1">
        @for ($i = 1; $i <= $billing->total_session; $i++)
            @if ($i <= $billing->used_session)
                <span class="w-3.5 h-3.5 rounded-full bg-danger-600 inline-block"></span>
            @else
                <span class="w-3.5 h-3.5 rounded-full border border-danger-600 flex items-center justify-center text-black text-xs font-bold">✕</span>
            @endif
        @endfor
    </div>

    <!-- History -->
    @if ($billing->histories->isNotEmpty())
        <div class="pt-4">
            <div class="text-sm font-semibold text-gray-900 dark:text-white">History</div>
            <div class="pt-2">
                @foreach ($billing->histories as $history)
                    @php $date = \Carbon\Carbon::parse($history->date_of_session); @endphp
                    <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="w-1/4 text-sm font-medium text-gray-900 dark:text-white">{{ $date->format('d M Y') }}</div>
                        <div class="w-1/4 text-sm text-center text-gray-500 dark:text-gray-400">{{ $date->format('h:i A') }}</div>
                        <div class="w-2/4 text-sm text-right text-gray-700 dark:text-gray-300">{{ $history->action }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex items-center gap-2 pt-3 pb-3">
        <x-filament::button
            color="primary"
            x-on:click="$dispatch('open-modal', { id: 'check-off-session-modal-{{ $billing->id }}' })"
        >
            Check off session
        </x-filament::button>

        <x-filament::button
            color="gray"
            x-on:click="$dispatch('open-modal', { id: 'restore-session-modal-{{ $billing->id }}' })"
        >
            Restore most recent session
        </x-filament::button>
    </div>

    <!-- Check Off Session Modal -->
    <x-filament::modal id="check-off-session-modal-{{ $billing->id }}" width="sm">
        <x-slot name="heading">Check off session</x-slot>
        <div class="space-y-4">
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.defer="checkOffDate" max="{{ now()->toDateString() }}" />
            </x-filament::input.wrapper>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'check-off-session-modal-{{ $billing->id }}' })"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button
                    color="primary"
                    wire:click="submitCheckOffSession({{ $billing->id }})"
                >
                    Check off
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    <!-- Restore Session Modal -->
    <x-filament::modal id="restore-session-modal-{{ $billing->id }}" width="sm">
        <x-slot name="heading">Restore most recent session</x-slot>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Are you sure you want to restore the most recently checked-off session? This action cannot be undone.
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <x-filament::button
                    color="gray"
                    x-on:click="$dispatch('close-modal', { id: 'restore-session-modal-{{ $billing->id }}' })"
                >
                    Cancel
                </x-filament::button>
                <x-filament::button
                    color="danger"
                    wire:click="confirmRestoreSession({{ $billing->id }})"
                >
                    Restore
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

</div>
@endforeach


=====


<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

class ViewUser extends ViewRecord
{
    use MutateBeforeFills, HasResetPassword;

    protected static string $resource = UserResource::class;
    protected static string $view = 'filament.resources.users.view-user';

    public $checkOffDate;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->checkOffDate = now()->toDateString();
    }

    // Check off session
    public function submitCheckOffSession(int $punchCardId): void
    {
        $date = $this->checkOffDate ?? now()->toDateString();
        $punchCard = $this->getPunchCardOrNotify($punchCardId);
        if (!$punchCard) return;

        if ($punchCard->used_session >= $punchCard->total_session) {
            Notification::make()
                ->title('No sessions left')
                ->warning()
                ->send();
            return;
        }

        $punchCard->increment('used_session');

        $this->addHistory(
            $punchCard,
            'Session checked off',
            Carbon::parse($date)->setTimeFromTimeString(now()->format('H:i:s'))
        );

        Notification::make()
            ->title('Session checked off')
            ->success()
            ->send();
    }

    // Restore most recent session
    public function confirmRestoreSession(int $punchCardId): void
    {
        $punchCard = $this->getPunchCardOrNotify($punchCardId);
        if (!$punchCard || $punchCard->used_session <= 0) {
            Notification::make()
                ->title('No sessions to restore')
                ->warning()
                ->send();
            return;
        }

        $punchCard->decrement('used_session');

        // Add history with last session date
        $lastHistory = $punchCard->histories()->latest('date_of_session')->first();
        $historyDate = $lastHistory?->date_of_session ?? now();

        $this->addHistory($punchCard, 'Session restored', Carbon::parse($historyDate));

        Notification::make()
            ->title('Most recent session restored')
            ->success()
            ->send();
    }

    private function getPunchCardOrNotify(int $punchCardId)
    {
        $punchCard = $this->record->trainerBillings()->whereKey($punchCardId)->first();
        if (!$punchCard) {
            Notification::make()
                ->title('Punch card not found')
                ->danger()
                ->send();
            return null;
        }
        return $punchCard;
    }

    private function addHistory($punchCard, string $action, ?Carbon $date = null): void
    {
        $punchCard->histories()->create([
            'action' => $action,
            'date_of_session' => $date ?? now(),
            'punch_card_id' => $punchCard->id,
        ]);
    }
}
