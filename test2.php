
 <div class="text-xs text-gray-500 dark:text-gray-400">
                Purchased {{ \Carbon\Carbon::parse($billing->purchased_at)->format('F d') }}
            </div>

css shoudl be 
font-family: DM Sans;
font-weight: 500;
font-style: Medium;
font-size: 14px;
leading-trim: NONE;
line-height: 16px;
letter-spacing: 0%;
vertical-align: middle;

color #374151


 <div class="text-xs text-gray-500 dark:text-gray-400">
                Sessions available
                <span class="font-medium text-gray-700 dark:text-gray-300">
                    {{ $billing->total_session - $billing->used_session }}
                    / {{ $billing->total_session }}
                </span>
            </div>
            
font-family: DM Sans;
font-weight: 700;
font-style: Bold;
font-size: 14px;
leading-trim: NONE;
line-height: 16px;
letter-spacing: 0%;
vertical-align: middle;
color : #000000

 
 
 @if ($billing->histories->isNotEmpty())
    <div class="pt-4">
        <div class="text-sm font-semibold text-gray-900 dark:text-white">History</div>
        <div class="pt-2">
            @foreach ($billing->histories as $history)
                @php $date = \Carbon\Carbon::parse($history->date_of_session); @endphp
                <div class="flex items-center py-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="w-1/4 text-sm font-medium text-gray-900 dark:text-white">{{ $date->format('d M Y') }}</div>
                    <div class="w-1/4 text-sm text-right text-gray-500 dark:text-gray-400">{{ $date->format('h:i A') }}</div>
                    <div class="w-2/4 text-sm text-right text-gray-700 dark:text-gray-300">{{ $history->action }}</div>
                </div>
            @endforeach
        </div>
    </div>
@endif

I need below css for {{ $date->format('d M Y') }}, {{ $date->format('h:i A') }} and {{ $history->action }}

font-family: DM Sans;
font-weight: 500;
font-style: Medium;
font-size: 14px;
leading-trim: NONE;
line-height: 16px;
letter-spacing: 0%;
vertical-align: middle;


colro #444444