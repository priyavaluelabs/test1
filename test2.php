 public function trainerBillings()
    {
        return tap(
            $this->setConnection('mysql')->hasMany(
                PTBillingUserPunchCard::class,
                'user_id',
                'id'
            )
            ->with('histories')
            ->orderBy('purchased_at', 'desc'),
            function ($query) {
                $this->setConnection('liftbrands');
            }
        );
    }



    <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PTBillingUserPunchCard extends Model
{
    protected $table = 'pt_billing_user_punch_cards';
    
    protected $fillable = [
        'user_id',
        'trainer_id',
        'product_name',
        'total_session',
        'used_session',
        'purchased_at'
    ];

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(NomergyUser::class, 'user_id', 'id');
    }

    public function histories()
    {
        return $this->hasMany(
            PTBillingUserPunchCardHistory::class,
            'punch_card_id',
            'id'
        )->orderBy('id', 'desc');
    }
}