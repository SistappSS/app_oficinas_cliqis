<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Modules\UserModulePermission;
use App\Models\Modules\Module;
use App\Models\Entities\Users\User;
use App\Models\Entities\Users\CustomerUserLogin;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';
    protected $description = 'Remove módulos expirados, resincroniza roles e desativa usuários sem módulos ativos';

    public function handle()
    {
        $this->info("🔍 Verificando módulos expirados...");

        $expired = UserModulePermission::where('expires_at', '<', now())->get();

        if ($expired->isEmpty()) {
            $this->info("✅ Nenhum módulo expirado encontrado.");
            return;
        }

        $affectedUsers = collect();

        foreach ($expired as $ump) {
            $affectedUsers->push($ump->user_id);
            $ump->delete();
        }

        $this->info("⚠️ Módulos expirados removidos para usuários: " . implode(', ', $affectedUsers->unique()->toArray()));

        foreach ($affectedUsers->unique() as $userId) {
            $activeModules = UserModulePermission::where('user_id', $userId)
                ->where('expires_at', '>', now())
                ->pluck('module_id');

            $roles = Module::whereIn('id', $activeModules)
                ->pluck('permission')
                ->filter()
                ->unique()
                ->all();

            $user = User::find($userId);

            if (!$user) {
                continue;
            }

            $user->syncRoles($roles);

            if ($activeModules->isEmpty()) {
                CustomerUserLogin::where('user_id', $userId)->update(['subscription' => 0]);
                $this->warn("🚫 Usuário {$userId} sem módulos ativos: assinatura desativada.");
            } else {
                $this->info("🔑 Usuário {$userId} ainda possui módulos ativos: roles sincronizadas.");
            }
        }

        $this->info("✅ Verificação concluída!");
    }
}
