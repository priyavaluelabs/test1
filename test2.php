$trainerSetting = TrainerSetting::where('user_id', auth()->id())->first();
$this->terms = $trainerSetting->terms ?? '';

