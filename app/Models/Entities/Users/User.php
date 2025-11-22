<?php

namespace App\Models\Entities\Users;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Authenticate\AdditionalCustomerInfo;
use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Partners\Partner;
use App\Models\Entities\Representatives\Representative;
use App\Models\Inventories\Products\Product;
use App\Models\Modules\UserFeature;
use App\Models\Modules\UserModulePermission;
use App\Models\Supports\SupportChat\SupportChat;
use App\Traits\HasSubscriptionCheck;
use App\Traits\RoleCheckTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use hasRoles;
    use TwoFactorAuthenticatable;
    use RoleCheckTrait;
    use HasSubscriptionCheck;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function customerLogin()
    {
        return $this->hasOne(CustomerUserLogin::class, 'user_id');
    }

    public function hasCustomerLogin(): bool
    {
        return (bool) ($this->customerLogin && $this->customerLogin->customer_sistapp_id);
    }

    public function hasSegment(): bool
    {
        return AdditionalCustomerInfo::where('user_id', $this->id)
            ->whereNotNull('segment')->exists();
    }

    public function hasAddons(): bool
    {
        if ($this->hasRole('admin')) return true;

        return UserModulePermission::where('user_id', $this->id)->exists();
    }

    public function additionalInfo()
    {
        return $this->hasOne(AdditionalCustomerInfo::class, 'user_id');
    }

    public function nextOnboardingRoute(): ?string
    {
        if ($this->hasRole('admin')) return null;

        if (!$this->hasCustomerLogin()) return 'additional-customer-info.index';
        if (!$this->hasSegment())      return 'company-segment.index';
        if (!$this->hasAddons())       return 'addons.index';
        return null;
    }

    public function hasActiveOrTrial(): bool
    {
        $login = $this->customerLogin;
        if (!$login) return false;
        $active  = (bool) $login->subscription;
        $inTrial = $login->trial_ends_at && now()->lt($login->trial_ends_at);
        return $active || $inTrial;
    }

    public function hasPaidSubscription(): bool
    {
        return (int) optional($this->customerLogin)->subscription === 1;
    }

    public function isOnTrial(): bool
    {
        $cl = $this->customerLogin;
        return $cl
            && (int)$cl->subscription === 0
            && $cl->trial_ends_at
            && now()->lt($cl->trial_ends_at);
    }

    public function trialEndsAt(): ?Carbon
    {
        $raw = optional($this->customerLogin)->trial_ends_at;

        if (!$raw) return null;

        return $raw instanceof Carbon ? $raw : Carbon::parse($raw);
    }

    public function canAuthenticate()
    {
        if ($this->hasActiveSubscription()) {
            return true;
        }

        session()->flash('error', 'Seu período de teste expirou e você não possui uma assinatura ativa.');

        return false;
    }

    public function modulePermissions()
    {
        return $this->hasMany(UserModulePermission::class, 'user_id');
    }

    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }

    public function messages()
    {
        return $this->hasMany(SupportChat::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_sistapp_id', 'customer_sistapp_id');
    }

    public function syncRolesFromFeatures(): void
    {
        // Busca todas as features ativas do usuário
        $activeFeatures = UserFeature::whereHas('userModulePermission', function ($q) {
            $q->where('user_id', $this->id)
                ->where('expires_at', '>', now());
        })->with('feature')->get();

        // Junta todas as roles em um único array sem duplicação
        $roles = $activeFeatures->pluck('feature.roles')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->syncRoles($roles);
    }
}
