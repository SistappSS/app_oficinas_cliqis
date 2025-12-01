<?php

namespace Database\Seeders\Users;

use App\Models\Authenticate\Permissions\Permission;
use App\Models\Authenticate\Permissions\Role;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use Illuminate\Database\Seeder;


class UserSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'entitie_user_create',
            'entitie_user_view',
            'entitie_user_edit',
            'entitie_user_delete',

            'entitie_customer_create',
            'entitie_customer_view',
            'entitie_customer_edit',
            'entitie_customer_delete',

            'sales_service_create',
            'sales_service_view',
            'sales_service_edit',
            'sales_service_delete',

            'sales_contract_create',
            'sales_contract_view',
            'sales_contract_edit',
            'sales_contract_delete',

            'sales_invoice_create',
            'sales_invoice_view',
            'sales_invoice_edit',
            'sales_invoice_delete',

            'sales_billing_create',
            'sales_billing_view',
            'sales_billing_edit',
            'sales_billing_delete',

            'finance_payable_create',
            'finance_payable_view',
            'finance_payable_edit',
            'finance_payable_delete',

            'finance_receivable_create',
            'finance_receivable_view',
            'finance_receivable_edit',
            'finance_receivable_delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($permissions);

        Role::firstOrCreate(['name' => 'segment_authorized']);

        Role::firstOrCreate(['name' => 'employee_authorized']);

        $user = User::firstOrCreate([
            'name' => 'Teste admin',
            'email' => 'teste@teste.com',
            'password' => bcrypt('teste123'),
            'is_active' => true,
        ])->assignRole($adminRole);

        $prefix = 'sist_';
        $randomNumber = mt_rand(100000, 999999);

        $customerSistappId = $prefix . $randomNumber;

        CustomerUserLogin::firstOrCreate([
            'user_id' => $user->id,
            'customer_id' => 0,
            'customer_sistapp_id' => $customerSistappId,
            'trial_ends_at' => '2030-01-01 18:00:00',
            'is_master_customer' => 1,
            'subscription' => 1
        ]);
    }
}
