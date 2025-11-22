<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class SubscriptionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $expiresAt;
    protected $modules;

    public function __construct($expiresAt, $modules = [])
    {
        $this->expiresAt = $expiresAt;
        $this->modules = $modules;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $dateFormatted = Carbon::parse($this->expiresAt)->format('d/m/Y H:i');
        $modulesList = implode(', ', $this->modules);

        return (new MailMessage)
            ->subject('⚠️ Sua assinatura está prestes a expirar')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Identificamos que sua assinatura está prestes a expirar.")
            ->when(!empty($modulesList), fn($msg) =>
            $msg->line("Módulos afetados: {$modulesList}")
            )
            ->line("Data de vencimento: **{$dateFormatted}**")
            ->line("Após esta data, você poderá perder acesso aos módulos mencionados.")
            ->action('Renovar agora', url(route('billing.index', $notifiable->id)))
            ->line('Se você já efetuou o pagamento, desconsidere este aviso.');
    }
}
