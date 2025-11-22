<?php

namespace App\Http\Controllers\APP\Entities\Users;

use App\Http\Controllers\Controller;
use App\Traits\WebIndex;

class UserController extends Controller
{
    use WebIndex;

    public function index()
    {
        return $this->webRoute('app.entities.user.user_index', 'user');
    }
}
