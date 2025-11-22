<?php

namespace App\Services\Modules;

use App\Models\Entities\Users\User;
use Spatie\Permission\Models\Role;

class RoleSyncService
{
    public function ensureSegmentRole(User $user): void
    {
        $segment = optional($user->additionalInfo)->segment;
        if (!$segment) return;

        Role::findOrCreate($segment, 'web');

        if (!$user->hasRole($segment)) {
            $user->assignRole($segment);
        }
    }

    public function syncFromFeatures(User $user): void
    {
        // seu método já agrega roles das features ativas
        $user->syncRolesFromFeatures();

        // mantém a role do segmento aplicada
        $this->ensureSegmentRole($user);
    }
}
