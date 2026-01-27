<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pttab extends Component
{
    /**
     * Get the view / contents that represent the component.
     */
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
        return request()->is($path) || request()->is($path.'/*');
    }
}



<div wire:ignore {{ $attributes->class(['border-b rounded-t-lg']) }} >
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
        sm:space-x-1
        md:space-x-8
        2xl:space-x-20
    "
    x-bind:class="$store.sidebar.isOpen ? 'xl:space-x-10' : 'xl:space-x-20'"
    >
        @foreach($getPTTabs as $getPTTab)
            <li>
                <a 
                    href="{{ url($getPTTab['path']) }}" 
                    class="
                        inline-flex 
                        items-center 
                        justify-center 
                        p-4 
                        border-b-2
                        rounded-t-lg 
                    @if($isActive($getPTTab['path']))
                        border-primary-600
                        dark:border-primary-500 
                        dark:text-primary-500 
                        text-primary-600"
                    @else
                        border-transparent 
                        hover:text-gray-600 
                        hover:border-gray-300
                        dark:hover:text-gray-300 group
                    @endif
                    "
                >
                    @php
                        $iconClasses = "w-5 h-5 mr-2";
                        $iconClasses = $isActive($getPTTab['path']) ? $iconClasses . " text-primary-600 dark:text-primary-500" : $iconClasses . " text-gray-400 group-hover:text-gray-500 dark:text-gray-500 dark:group-hover:text-gray-300";
                    @endphp
                    
                    <x-filament::icon
                        icon="{{ $getPTTab['icon'] }}"
                        :class="$iconClasses"
                    />

                    <svg class="
                            w-4 h-4 mr-2 hidden
                            @if($isActive($getPTTab['path'])) 
                                text-primary-600 
                                dark:text-primary-500
                            @else
                                text-gray-400 
                                group-hover:text-gray-500 
                                dark:text-gray-500 
                                dark:group-hover:text-gray-300
                            @endif
                        " 
                        aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
                    </svg> 
                    {{ $getPTTab['name'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
