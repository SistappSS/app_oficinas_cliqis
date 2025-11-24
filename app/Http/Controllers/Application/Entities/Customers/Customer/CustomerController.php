<?php

namespace App\Http\Controllers\Application\Entities\Customers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\Customers\StoreCustomerRequest;
use App\Http\Requests\Entities\Customers\UpdateCustomerRequest;
use App\Models\Entities\Customers\Customer;
use App\Models\Entities\Users\CustomerUserLogin;
use App\Models\Entities\Users\User;
use App\Traits\CreateCustomerAsaas;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{

}
