<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\ClubGlofoxStatusService;

class Pttab extends Component
{
    public bool $isGlofoxVerified = true;

    public function __construct()
    {
        $user = Auth::user();

        if ($user) {
            $clubs = app(ClubGlofoxStatusService::class)
                ->getForUser($user);

            // ❗ If ANY club is not verified → disable tabs
            $this->isGlofoxVerified = collect($clubs)
                ->every(fn ($club) => $club['is_verified'] === true);
        }
    }

    public function render(): View|Closure|string
    {
        return view('components.pt-tab');
    }

    public function getPTTabs(): array
    {
        return [
            [
                'path' => 'pt/home',
                'name' => __('labels.home'),
                'icon' => 'heroicon-o-home',
            ],
            [
                'path' => 'pt/exercises',
                'name' => __('labels.exercises'),
                'icon' => 'f-exercise-running',
            ],
            [
                'path' => 'pt/workouts',
                'name' => __('labels.workouts'),
                'icon' => 'dumbbell-o',
            ],
            [
                'path' => 'pt/programs',
                'name' => __('labels.programs'),
                'icon' => 'heroicon-o-rectangle-stack',
            ],
            [
                'path' => 'pt/assigns',
                'name' => __('labels.assign'),
                'icon' => 'heroicon-o-clipboard-document-list',
            ],
        ];
    }

    public function isActive($path): bool
    {
        return request()->is($path) || request()->is($path . '/*');
    }
}



=====



@foreach($getPTTabs as $getPTTab)
    <li>
        @php
            $disabled = ! $isGlofoxVerified;
        @endphp

        <a
            @if(! $disabled)
                href="{{ url($getPTTab['path']) }}"
            @endif
            class="
                inline-flex
                items-center
                justify-center
                p-4
                border-b-2
                rounded-t-lg
                {{ $disabled ? 'opacity-40 cursor-not-allowed pointer-events-none' : '' }}

                @if($isActive($getPTTab['path']) && ! $disabled)
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
            <x-filament::icon
                icon="{{ $getPTTab['icon'] }}"
                class="w-5 h-5 mr-2"
            />

            {{ $getPTTab['name'] }}

            @if($disabled)
                <span class="ml-2 text-xs text-red-600">
                    (Verification required)
                </span>
            @endif
        </a>
    </li>
@endforeach

