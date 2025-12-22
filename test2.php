<x-filament::confirmation-modal
    id="restore-session-modal"
    heading="Restore most recent session"
    subheading="Are you sure you want to restore the most recently checked-off session? This action cannot be undone."
    confirm-label="Restore"
    cancel-label="Cancel"
    confirm-color="danger"
    wire:click="confirmRestoreSession({{ $selectedBillingId }})"
/>
