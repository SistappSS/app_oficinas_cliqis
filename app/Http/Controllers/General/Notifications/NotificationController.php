<?php

namespace App\Http\Controllers\General\Notifications;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('app.retails.notification.notification_index', compact('notifications'));
    }
}
