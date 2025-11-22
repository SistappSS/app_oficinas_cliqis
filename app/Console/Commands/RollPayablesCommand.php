<?php

namespace App\Console\Commands;

use App\Models\Finances\Payables\AccountPayable;
use App\Models\Finances\Payables\AccountPayableRecurrence;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollPayablesCommand extends Command
{
    protected $signature = 'payables:roll {--since=} {--dry}';
    protected $description = 'Gera recorrências para payables permanentes (sem end_recurrence)';

    public function handle()
    {
        $today = now();

// Range alvo: mês atual (por padrão)
        $rangeStart = $this->option('since')
            ? \Carbon\Carbon::parse($this->option('since'))->startOfMonth()
            : $today->copy()->startOfMonth();

        $rangeEnd = $today->copy()->endOfMonth(); // SEMPRE fim do mês atual

        $q = \App\Models\Finances\Payables\AccountPayable::query()
            ->whereNull('end_recurrence')
            ->where('status', '!=', 'canceled')          // não gere para cancelados
            ->whereIn('recurrence', ['monthly','yearly']);

        $q->chunkById(500, function($chunk) use (&$count, $rangeStart, $rangeEnd, $today) {
            foreach ($chunk as $ap) {

                $first = \Carbon\Carbon::parse($ap->first_payment);
                $anchorDay = $first->day;
                $anchorMonth = $first->month;

                // cursor inicial para o range (mês a mês ou ano a ano)
                $cursor = $rangeStart->copy();

                while ($cursor->lte($rangeEnd)) {
                    if ($ap->recurrence === 'monthly') {
                        // parcela do mês do cursor
                        $due = $this->clampDay($cursor->year, $cursor->month, $anchorDay);

                        // só gere se a âncora já "começou" (não gere meses/anos antes do first_payment)
                        if ($due->gte($first->copy()->startOfDay())) {
                            if ($this->createIfMissing($ap, $due)) $count++;
                        }

                        $cursor->addMonth(); // próximo mês

                    } else { // yearly
                        // gera somente no mês âncora
                        if ($cursor->month === $anchorMonth) {
                            $due = $this->clampDay($cursor->year, $anchorMonth, $anchorDay);
                            if ($due->gte($first->copy()->startOfDay())) {
                                if ($this->createIfMissing($ap, $due)) $count++;
                            }
                        }
                        // avança 1 mês (loop externo controla meses), mas só cria no mês âncora
                        $cursor->addMonth();
                    }
                }
            }
        });

        $this->info("Criadas/asseguradas $count parcelas.");

        return 0;
    }

    protected function clampDay(int $year, int $month, int $anchorDay): Carbon
    {
        $dt = Carbon::create($year, $month, 1)->startOfDay();
        $lastDay = $dt->copy()->endOfMonth()->day;
        $day = min($anchorDay, $lastDay);
        return $dt->copy()->day($day);
    }

    protected function nextDueFrom($lastDue, string $recurrence, int $anchorDay): Carbon
    {
        $d = Carbon::parse($lastDue);
        if ($recurrence==='monthly') $d->addMonth();
        else $d->addYear();
        return $this->clampDay($d->year, $d->month, $anchorDay);
    }

    protected function createIfMissing(AccountPayable $ap, Carbon $due): bool
    {
        // idempotência: tenta inserir se não existir (unique cuida)
        try {
            return DB::transaction(function() use ($ap, $due) {
                $exists = AccountPayableRecurrence::where('account_payable_id',$ap->id)
                    ->whereDate('due_date', $due->toDateString())->exists();
                if ($exists) return false;

                $nextNumber = (int) AccountPayableRecurrence::where('account_payable_id',$ap->id)->max('recurrence_number') + 1;

                AccountPayableRecurrence::create([
                    'customer_sistapp_id' => $ap->customer_sistapp_id,
                    'user_id'             => $ap->user_id,
                    'account_payable_id'  => $ap->id,
                    'recurrence_number'   => $nextNumber,
                    'due_date'            => $due->toDateString(),
                    'amount'              => $ap->default_amount,
                    'status'              => 'pending',
                ]);
                return true;
            }, 1);
        } catch (\Throwable $e) {
            // colisão de unique ou outra falha — ignora silenciosamente p/ idempotência
            return false;
        }
    }
}
