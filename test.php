<?php namespace Klyp\Nomergy\Models;

use Klyp\Nomergy\Events\UserRegistered;
use Klyp\Nomergy\Models\UserPortal as ModelsUserPortal;
use Klyp\Nomergy\Services\UserService;
use October\Rain\Database\Traits\SoftDelete;

class UserPortal extends Model
{
    use SoftDelete;
    
    public static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            static::handleFlexUserCreation($user);
        });

        static::updated(function ($user) {
            static::handleFlexUserUpdation($user);
        });
        
        static::deleted(function ($user) {
            static::handleDeletion($user);
        });
    }

    public $connection = 'mysql_portal';

    public $table = 'users';

    public $fillable = [
        'first_name',
        'last_name',
        'email',
        'login',
        'corp_partner_id',
        'accessible_clubs',
        'ma_user_id',
        'is_enabled_challenges',
        'is_onboarded',
        'stripe_account_id',
    ];

    public $casts = [
        'accessible_clubs' => 'array'
    ];

    public $belongsToMany = [
        'roles' => [
            FodRole::class,
            'table' => 'fod_role_user',
            'key' => 'user_id',
            'otherKey' => 'fod_role_id',
            'pivot' => ['club_id']
        ]
    ];

    public function getClubs()
    {
        return Club::whereIn('id', $this->accessible_clubs)->get();
    }

    /**
     * Handles the creation of a new Flex user.
     *
     * @param UserPortal $maUser The MA user object.
     * @return void
     */
    public static function handleFlexUserCreation($maUser)
    {
        $flexUser = User::where('flex_user_id', $maUser->ma_user_id)->first();

        if($flexUser) {
            return static::handleFlexUserUpdation($maUser);
        }

        $flexUser = User::create([
            'username'      => app(UserService::class)->getUniqueUsername($maUser->email),
            'email'         => $maUser->email,
            'flex_user_id'  => $maUser->ma_user_id,
            'first_name'    => $maUser->first_name,
            'last_name'     => $maUser->last_name,
            'is_fod'        => true,
            'country_id'    => Country::findCountryByTitle(request('country')),
            'timezone_id'   => Timezone::findTimezoneByTitle(request('timezone')),
        ]);

        $corporatePartner = static::getCorporatePartnerForFlexUser($maUser);
        $club = static::getClubForFlexUser();

        $attributes = [
            'corporate_partner_id' => $corporatePartner->id
        ];

        if ($club) {
            $attributes['club_id'] = $club->id;
        } else {
            // In case of MA user remove facility from accessible_clubs, we do not have primary facility
            // In that case, just get the first club from corporate partner

            $relatedClubs = $corporatePartner->getRelatedClubs();
            
            if($relatedClubs->isNotEmpty()) {
                $attributes['club_id'] = $relatedClubs->first();
            }
        }

        $flexUser->profile()->update($attributes);

        if ($club->enable_on_demand_class) {
            $flexUser->meta()->update([
                'is_show_fod' => true,
            ]);
        }

        $subscription = Subscription::where('sku', 'FLEX')->isPublished()->first();

        event(new UserRegistered($flexUser, $subscription));
    }

    /**
     * Handles the updating of an existing Flex user.
     *
     * @param UserPortal $maUser The MA user object.
     * @return void
     */
    public static function handleFlexUserUpdation($maUser)
    {
        $flexUser = User::where('flex_user_id', $maUser->ma_user_id)->first();

        if( !$flexUser) {
            return static::handleFlexUserCreation($maUser);
        }

        $flexUser->update([
            'email'         => $maUser->email,
            'first_name'    => $maUser->first_name,
            'last_name'     => $maUser->last_name,
        ]);

        $corporatePartner = static::getCorporatePartnerForFlexUser($maUser);
        $club = static::getClubForFlexUser();

        $attributes = [
            'corporate_partner_id' => $corporatePartner->id
        ];

        if ($club) {
            $attributes['club_id'] = $club->id;
        }

        $flexUser->profile()->update($attributes);

        if ($club && $club->enable_on_demand_class) {
            $flexUser->meta()->update([
                'is_show_fod' => true,
            ]);
        }
    }

    /**
     * Handles the deletion of an existing MA user.
     *
     * @param UserPortal $maUser The MA user object.
     * @return void
     */
    public static function handleDeletion($maUser)
    {
        ModelsUserPortal::withoutEvents(function () use ($maUser) {
            $maUserId = $maUser->ma_user_id;

            $maUser->update([
                'first_name' => '',
                'last_name' => '',
                'email' => $maUserId,
                'login' => $maUserId,
            ]);
        });
    }

    /**
     * Gets the club for the Flex user.
     *
     * @return Club
     */
    public static function getClubForFlexUser()
    {
        $facilityId = request('primary_facility');

        return Club::getClubByFacilityId($facilityId);
    }

    /**
     * Gets the corporate partner for the Flex user.
     *
     * @param UserPortal $maUser The MA user object.
     * @return CorporatePartner
     */
    public static function getCorporatePartnerForFlexUser($maUser)
    {
        $corporatePartnerId = $maUser->corp_partner_id;

        return CorporatePartner::where('id', $corporatePartnerId)->first();
    }
}