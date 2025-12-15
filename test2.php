 public function form(Form $form): Form
    {
        return $form->schema([
            RichEditor::make('terms')
                ->label(false)
                ->placeholder('Enter your T&Cs here')
                ->columnSpanFull()
                ->toolbarButtons([
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'bulletList',
                    'orderedList',
                    'link',
                    'blockquote',
                    'undo',
                    'redo',
                ])
            ]);
    }

    public function save()
    {
        TrainerSetting::updateOrCreate(
            ['user_id' => auth()->id()],
            ['terms' => $this->form->getState()['terms']]
        );

        Notification::make()
            ->title(__('stripe.trainer_terms_updated'))
            ->success()
            ->send();
    }