<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

trait RoleCheckTrait
{
    private function userHasRole($role)
    {
        return Auth::check() && Auth::user()->hasRole($role);
    }

    private function customerSistappID()
    {
        return Auth::user()->customerLogin->customer_sistapp_id;
    }

    private function trialEnds()
    {
        return $this->userHasRole('admin')
            ? Carbon::now()->addDays(14)
            : Auth::user()->customerLogin->trial_ends_at;
    }

    private function subscription()
    {
        return $this->userHasRole('admin')
            ? true
            : Auth::user()->customerLogin->subscription;
    }

    private function userAuth()
    {
        return Auth::user()->id;
    }

    private function determineUserRole($request)
    {
        return $this->userHasRole('admin') ? $request->role : 'customer_customer_cliqis';
    }
}
