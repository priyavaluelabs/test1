<x-filament::button 
    tag="button" 
    wire:click="goToEditProfile"
    class="inline-flex items-center hover:text-primary-600 transition">
    <x-heroicon-o-pencil-square class="w-4 h-4 text-[#444444]" />
</x-filament::button>


public function goToEditProfile()
{
    return redirect()->route('filament.admin.pages.edit-profile');
}
