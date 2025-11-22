<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Schedule::command('normalize:customer')->everyTenMinutes();
//
//Schedule::command('register:monthly-overdue')->monthlyOn(1, 0);
//Schedule::command('register:yearly-overdue')->yearlyOn(1, 0);
//

// Gera recorrencia de contas a pagar
Schedule::command('payables:roll')->monthlyOn(1, '02:10')->withoutOverlapping();
Schedule::command('payables:roll')->monthlyOn(30, '02:10')->withoutOverlapping();

Schedule::command('invoices:generate-recurring')->dailyAt('01:00');
Schedule::command('invoices:send-reminders')->hourly(); // ou dailyAt('09:00')

// Job para expirar módulos
Schedule::command('subscriptions:check-expired')->dailyAt('02:00');

// Job para avisar 3 dias antes do vencimento
Schedule::command('subscriptions:notify-expiring 3')->dailyAt('09:00');

//$schedule->job(new SendInvoiceRemindersJob())
//    ->dailyAt('09:00');
