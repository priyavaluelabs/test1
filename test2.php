<?php

namespace App\Filament\Resources;

use App\Filament\Enum\Role;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\RelationManagers\JourneyBadgeRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OnboardingAnswersRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\OrderRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\PersonalBestsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\SubscriptionRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\WorkoutLogsRelationManager;
use App\Filament\Utils\Helper;
use App\Models\Club;
use App\Models\NomergyUser;
use App\Filament\Enum\MembershipStatus as MembershipStatusEnum;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $slug = 'nomergy-users';

    protected static ?string $model = NomergyUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationGroup = 'Engage';

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $modelLabel = 'Member';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Card::make()
                            ->reactive()
                            ->schema([
                                TextInput::make('id')
                                    ->label(__('labels.id'))
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('username')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('first_name')
                                    ->rules(['required', 'max:150']),

                                TextInput::make('last_name')
                                    ->rules(['required', 'max:150']),

                                TextInput::make('email')
                                    ->rules(['required', 'email'])
                                    ->unique(table: NomergyUser::class, ignoreRecord: true)
                                    ->reactive(),

                                TextInput::make('phone')
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('myzone_status')
                                    ->label(__('labels.myzone_status'))
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('onboarded')
                                    ->label(__('labels.onboarded'))
                                    ->disabled()
                                    ->dehydrated(false),

                                TagsInput::make('goals')
                                    ->label(__('labels.goals'))
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('persona')
                                    ->disabled()
                                    ->dehydrated(false),

                                
                            ])
                            ->columns(2),
                        // Trainer Billing (View + Edit)
                        Card::make()
                            ->schema([
                                Placeholder::make('billing')
                                    ->label('')
                                    ->content(function (NomergyUser $record) {
                                        return view('pages.users.partials.billing', [
                                            'billings' => $record->trainerBillings,
                                        ]);
                                    }),
                            ])
                            ->visible(fn (?NomergyUser $record, string $context): bool =>
                                $record !== null && in_array($context, ['view', 'edit'])
                            ),
                        Section::make(__('labels.digital_door_access'))
                            ->schema([
                                Placeholder::make('activationLimit')
                                    ->label('')
                                    ->content(function (NomergyUser $record, $livewire, $get) {
                                        return view('pages.users.partials.activation-limit', [
                                            'record' => $record,
                                            'livewire' => $livewire,
                                            'get' => $get,
                                        ]);
                                    }),
                            ])
                            ->visible(fn (NomergyUser $record, $context): bool => 
                                config('app.is_snap_env') && 
                                $context === 'edit' && $record->isDdaEnabled() && 
                                Auth::user()->getManagerialAccessClubs()->contains($record->profile->club_id)
                            ),
                    ])
                    ->columnSpan(['lg' => fn (?NomergyUser $record) => $record === null ? 3 : 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Card::make()
                            ->schema([
                                Placeholder::make('created_at')
                                    ->label(__('labels.created_at'))
                                    ->content(fn (NomergyUser $record): string => $record->created_at->diffForHumans()),

                                Placeholder::make('updated_at')
                                    ->label(__('labels.last_modified_at'))
                                    ->content(fn (NomergyUser $record): string => $record->updated_at->diffForHumans()),

                                Placeholder::make('last_active_utc')
                                    ->label(__('labels.last_app_usage_utc'))
                                    ->content(function (NomergyUser $record): string {
                                        $lastActive = $record->last_active;
                                        if (empty($lastActive)) {
                                            return '-';
                                        }

                                        try {
                                            // desired format DD Month YYYY - HH:MM (e.g., 05 May 2025 - 18:54)
                                            return $lastActive->format('d F Y - H:i');
                                        } catch (\Throwable $e) {
                                            return '-';
                                        }
                                    }),
                            ]),

                        Card::make()
                            ->schema([
                                Placeholder::make('club')
                                    ->label(__('labels.club'))
                                    ->content(fn (NomergyUser $record): string => $record->profile->club->title),

                                Placeholder::make('glofoxClub')
                                    ->label(__('labels.glofox_club'))
                                    ->content(function (NomergyUser $record, $livewire, $get) {
                                        return view('pages.users.partials.glofox-status', [
                                            'record' => $record,
                                            'initialEmail' => $livewire->initialEmail,
                                            'get' => $get,
                                        ]);
                                    })
                                    ->visible(fn (NomergyUser $record): bool => filled($record->profile->glofoxClubTitle)),

                                Placeholder::make('verifyGlofox')
                                    ->label('')
                                    ->content(function (NomergyUser $record, $livewire, $get) {
                                        return view('pages.users.partials.verify-glofox', [
                                            'record' => $record,
                                            'initialEmail' => $livewire->initialEmail,
                                            'get' => $get,
                                        ]);
                                    })
                                    ->visible(fn (NomergyUser $record, $context): bool => filled($record->profile->glofoxClubTitle)
                                        && Auth::user()->getManagerialAccessClubs()->contains($record->profile->club_id) && $context === 'edit'
                                    ),

                                Placeholder::make('membership_status_sidebar')
                                    ->label(function () {
                                        $tooltipHtml = MembershipStatusEnum::columnTooltipHtml();
                                        return new HtmlString(view('pages.users.partials.member-status-label', compact('tooltipHtml'))->render());
                                    })
                                    ->content(function (NomergyUser $record): string {
                                        $statusId = optional($record->liftit)->status_id;
                                        return MembershipStatusEnum::fromLiftitStatusId($statusId)->label();
                                    }),

                                
                            ])
                            ->hidden(fn () => ! config('app.is_snap_env')),

                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn (?NomergyUser $record) => $record === null),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),

                TextColumn::make('full_name')
                    ->label(__('labels.full_name'))
                    ->searchable(['klyp_nomergy_users.first_name', 'klyp_nomergy_users.last_name']),

                TextColumn::make('email')
                    ->getStateUsing(fn (NomergyUser $record) => Helper::getObfuscatedEmail(Auth::user(), $record))
                    ->searchable(['klyp_nomergy_users.email']),

                TextColumn::make('membership_status')
                    ->label(function () {
                        $tooltipHtml = MembershipStatusEnum::columnTooltipHtml();
                        return new HtmlString(view('pages.users.partials.member-status-label', compact('tooltipHtml'))->render());
                    })
                    ->getStateUsing(function (NomergyUser $record): string {
                        $statusId = optional($record->liftit)->status_id;
                        return MembershipStatusEnum::fromLiftitStatusId($statusId)->label();
                    })
                    ->hidden(fn () => ! config('app.is_snap_env')),

                TextColumn::make('profile.club.title')
                    ->searchable(),

                TextColumn::make('onboarded')
                    ->label(__('labels.onboarded')),
            ])
            ->filters([
                SelectFilter::make('club')
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty(($data['value']))) {
                            return $query;
                        }

                        return $query->where('klyp_nomergy_user_profiles.club_id', $data['value']);
                    })
                    ->searchable()
                    ->options(fn () => Club::whereIn('id', Auth::user()->accessible_clubs)->pluck('title', 'id')),

                SelectFilter::make('membership_status')
                    ->label(__('labels.membership_status'))
                    ->searchable()
                    ->options(MembershipStatusEnum::options())
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $group = MembershipStatusEnum::from($data['value']);
                        $ids = $group->liftitIds();

                        return $ids === null
                            ? $query->whereNull('klyp_nomergy_liftit_users.status_id')
                            : $query->whereIn('klyp_nomergy_liftit_users.status_id', $ids);
                    })
                    ->hidden(fn () => ! config('app.is_snap_env')),
            ])
            ->actions([
                EditAction::make(),

                ViewAction::make()
                    ->visible(function (NomergyUser $record) {
                        return ! Auth::user()->getManagerialAccessClubs()->contains($record->club_id) || ! config('app.is_snap_env');
                    }),
            ])
            ->bulkActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            WorkoutLogsRelationManager::class,
            OnboardingAnswersRelationManager::class,
            PersonalBestsRelationManager::class,
            JourneyBadgeRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $accessibleClubs = Auth::user()->accessible_clubs;

        $accessibleClubs = is_array($accessibleClubs) ? $accessibleClubs : [];

        $query = parent::getEloquentQuery()
            ->select('klyp_nomergy_users.*', 'goals', 'club_id')
            ->join('klyp_nomergy_user_profiles', 'klyp_nomergy_user_profiles.user_id', '=', 'klyp_nomergy_users.id')
            ->whereIn('klyp_nomergy_user_profiles.club_id', $accessibleClubs)
            ->with([
                'profile.club',
                'profile.glofoxClub',
                'profile.corporatePartner',
                'meta',
                'liftit',
                'subscription' => function ($query) {
                    $query->isCurrent();
                },
                'trainerBillings.histories',
            ]);

        $query->leftJoin('klyp_nomergy_liftit_users', 'klyp_nomergy_liftit_users.user_id', '=', 'klyp_nomergy_users.id')
            ->selectRaw('CASE 
                WHEN klyp_nomergy_liftit_users.status_id IN (1) THEN 1 
                WHEN klyp_nomergy_liftit_users.status_id IN (6) THEN 2 
                WHEN klyp_nomergy_liftit_users.status_id IN (3) THEN 3 
                WHEN klyp_nomergy_liftit_users.status_id IN (4,5) THEN 4 
                WHEN klyp_nomergy_liftit_users.status_id IN (2) THEN 5 
                ELSE 6 END as status_sort')
            ->orderBy('status_sort');

        return $query;
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'klyp_nomergy_users.id';
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->full_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name'];
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->hasAnyRole([
            Role::ZoneOwner,
            Role::ZoneManager,
            Role::ZoneInstructor,
            Role::ZoneStaff,
        ]);
    }

    public static function canEdit(Model $record): bool
    {
        if (! config('app.is_snap_env')) {
            return false;
        }

        return Auth::user()->getManagerialAccessClubs()->contains($record->profile->club_id);
    }
}
