<?php

namespace App\Traits;

use App\Models\Entities\Users\CustomerUserLogin;

trait HasSubscriptionCheck
{
    public function hasActiveSubscription(): bool
    {
        if ($this->userHasRole('admin')) {
            return true;
        }

        if (!$this->is_active) {
            return false;
        }

        $login = $this->customerLogin;

        if (!$login) {
            return false;
        }

        if ($login->is_master_customer) {
            return $this->checkTrialOrSubscription($login);
        } else {
            $master = CustomerUserLogin::where('customer_sistapp_id', $login->customer_sistapp_id)
                ->where('is_master_customer', true)
                ->first();

            return $master ? $this->checkTrialOrSubscription($master) : false;
        }
    }

    private function checkTrialOrSubscription($login): bool
    {
        return now()->lessThanOrEqualTo($login->trial_ends_at) || $login->subscription;
    }
}
