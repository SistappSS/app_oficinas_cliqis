<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Modules\UserModulePermission;
use App\Models\Modules\Module;
use App\Models\Entities\Users\User;
use App\Notifications\SubscriptionExpiringNotification;
use Carbon\Carbon;

class NotifyExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:notify-expiring {days=3}';
    protected $description = 'Notifica usuários com módulos a expirar em X dias (padrão: 3)';

    public function handle()
    {
        $days = (int) $this->argument('days');
        $targetDate = Carbon::now()->addDays($days);

        $this->info("🔔 Buscando módulos que expiram em {$targetDate->toDateString()}...");

        $permissions = UserModulePermission::whereDate('expires_at', '=', $targetDate->toDateString())
            ->get()
            ->groupBy('user_id');

        if ($permissions->isEmpty()) {
            $this->info("Nenhum módulo com vencimento em {$targetDate->format('d/m/Y')}.");
            return;
        }

        foreach ($permissions as $userId => $userPermissions) {
            $user = User::find($userId);
            if (!$user) continue;

            $modules = Module::whereIn('id', $userPermissions->pluck('module_id'))
                ->pluck('name')
                ->toArray();

            $expiresAt = $userPermissions->min('expires_at');

            $user->notify(new SubscriptionExpiringNotification($expiresAt, $modules));
            $this->info("📧 Notificação enviada para {$user->email} (User ID: {$user->id}).");
        }

        $this->info("✅ Notificações enviadas com sucesso.");
    }
}
