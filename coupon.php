<?php

namespace App\Jobs;

use App\Models\FodUserRole;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Glofox\Models\User\Staff;
use App\Models\User;
use App\Models\Club;

class VerifyGlofoxMember implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $this->resetAllGlofoxVerification();
        
        $accessibleClubs = Club::whereIn('id', $this->user->getAccessibleClubs())->get();

        $userEmail = strtolower('priya.singh+1991@valuelabs.com');

        foreach ($accessibleClubs as $club) {
            if (empty($club->glofox_branch_id)) {
                continue;
            }

            $glofoxConfig = $this->getGlofoxConfig($club->glofox_branch_id);
            $response     = (new Staff($glofoxConfig))->get();
            $data = $response->data ?? [];
            if (!is_array($data)) {
                continue;
            }

            foreach ($data as $trainer) {
                if (!empty($trainer['email']) && strtolower($trainer['email']) === $userEmail &&
                    $trainer['branch_id']  == $club->glofox_branch_id
                ) {
                    FodUserRole::where('club_id', $club->id)
                        ->where('user_id', $this->user->id)
                        ->update([
                            'glofox_verified_at' => now(),
                        ]);

                    break;
                }
            }
        }
    }

    private function resetAllGlofoxVerification(): void
    {
        FodUserRole::where('user_id', $this->user->id)
            ->whereNotNull('glofox_verified_at')
            ->update([
                'glofox_verified_at' => null,
            ]);
    }

    private function getGlofoxConfig($branchId)
    {
        return [
            'api_key'       => $this->user->corporatePartner->glofox_api_key,
            'api_token'     => $this->user->corporatePartner->glofox_api_token,
            'branch_id'     => $branchId,
        ];
    }
}
