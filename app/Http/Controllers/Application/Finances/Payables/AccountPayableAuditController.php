<?php

namespace App\Http\Controllers\Application\Finances\Payables;

use App\Http\Controllers\Controller;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountPayableAuditController extends Controller
{
    use RoleCheckTrait;

    private function isUuidLike($v): bool
    {
        return is_string($v) && (bool) preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $v
            );
    }

    private function decodeJson($v)
    {
        if (is_array($v) || is_object($v)) return $v;
        if (!is_string($v) || trim($v) === '') return null;
        $j = json_decode($v, true);
        return json_last_error() === JSON_ERROR_NONE ? $j : null;
    }

    public function index(Request $r)
    {
        $tenantId = $this->customerSistappID();

        $data = $r->validate([
            'start' => ['nullable', 'date'],
            'end'   => ['nullable', 'date'],

            'action' => ['nullable', 'string', 'max:40'],
            'user_id' => ['nullable', 'string', 'max:36'],

            'entity' => ['nullable', 'string', 'max:40'],
            'entity_id' => ['nullable', 'string', 'max:36'],

            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:200'],
        ]);

        $perPage = (int)($data['per_page'] ?? 30);

        // labels pt-br (padrão)
        $ACTION_LABEL = [
            'paid' => 'Baixa realizada',
            'created' => 'Criado',
            'updated' => 'Atualizado',
            'deleted' => 'Excluído',
            'toggled' => 'Ativado/Desativado',
            'canceled' => 'Cancelado',
            'amount_changed' => 'Valor alterado',
        ];

        $ENTITY_LABEL = [
            'recurrence' => 'Parcela',
            'payable' => 'Conta a pagar',
            'payment' => 'Pagamento',
            'custom_field' => 'Campo adicional',
            'adjustment' => 'Ajuste',
        ];

        $KNOWN_ACTIONS = array_keys($ACTION_LABEL);

        $q = DB::table('account_payable_audits as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->where('a.customer_sistapp_id', $tenantId)
            ->when(!empty($data['start']) && !empty($data['end']), function ($qq) use ($data) {
                $qq->whereBetween('a.created_at', [
                    $data['start'].' 00:00:00',
                    $data['end'].' 23:59:59'
                ]);
            })
            ->when(!empty($data['action']), fn($qq) => $qq->where('a.action', $data['action']))
            ->when(!empty($data['user_id']), fn($qq) => $qq->where('a.user_id', $data['user_id']))
            ->when(!empty($data['entity']), fn($qq) => $qq->where('a.entity', $data['entity']))
            ->when(!empty($data['entity_id']), fn($qq) => $qq->where('a.entity_id', $data['entity_id']))
            ->orderByDesc('a.created_at')
            ->select([
                'a.id',
                'a.entity',
                'a.entity_id',
                'a.action',
                'a.before',
                'a.after',
                'a.created_at',
                'a.user_id',
                DB::raw('COALESCE(u.name, "—") as user_name'),
            ]);

        $rows = $q->paginate($perPage);
        $items = collect($rows->items());

        // 1) normaliza registros antigos (swap action/entity_id quando vierem trocados)
        $normalized = $items->map(function ($x) use ($KNOWN_ACTIONS) {
            $entity = (string) $x->entity;
            $entityId = $x->entity_id ? (string) $x->entity_id : null;
            $action = (string) $x->action;

            // cenário bug antigo: action = UUID e entity_id = "created"/"toggled"/...
            if ($this->isUuidLike($action) && $entityId && in_array($entityId, $KNOWN_ACTIONS, true)) {
                [$action, $entityId] = [$entityId, $action];
            }

            $x->_entity_norm = $entity;
            $x->_entity_id_norm = $entityId;
            $x->_action_norm = $action;
            return $x;
        });

        // 2) resolve títulos/subtítulos por entidade
        $customFieldIds = $normalized
            ->filter(fn($x) => $x->_entity_norm === 'custom_field' && $this->isUuidLike($x->_entity_id_norm))
            ->pluck('_entity_id_norm')->unique()->values();

        $recurrenceIds = $normalized
            ->filter(fn($x) => $x->_entity_norm === 'recurrence' && $this->isUuidLike($x->_entity_id_norm))
            ->pluck('_entity_id_norm')->unique()->values();

        $customFieldsById = $customFieldIds->isEmpty()
            ? collect()
            : DB::table('payable_custom_fields')
                ->where('customer_sistapp_id', $tenantId)
                ->whereIn('id', $customFieldIds)
                ->select('id', 'name', 'type', 'active')
                ->get()
                ->keyBy('id');

        $recurrencesById = $recurrenceIds->isEmpty()
            ? collect()
            : DB::table('account_payable_recurrences as r')
                ->join('account_payables as ap', 'ap.id', '=', 'r.account_payable_id')
                ->where('r.customer_sistapp_id', $tenantId)
                ->whereIn('r.id', $recurrenceIds)
                ->select([
                    'r.id',
                    'r.due_date',
                    'r.recurrence_number',
                    'r.amount',
                    'ap.description',
                    'ap.times',
                    'ap.recurrence',
                ])
                ->get()
                ->keyBy('id');

        // users do tenant (select)
        $users = DB::table('users as u')
            ->join('customer_user_logins as cl', 'cl.user_id', '=', 'u.id')
            ->where('cl.customer_sistapp_id', $tenantId)
            ->select('u.id', 'u.name')
            ->orderBy('u.name')
            ->distinct()
            ->get();

        // filtros actions/entities (sem lixo/uuid)
        $actionsRaw = DB::table('account_payable_audits')
            ->where('customer_sistapp_id', $tenantId)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values()
            ->filter(fn($a) => !$this->isUuidLike((string)$a))
            ->values();

        $entitiesRaw = DB::table('account_payable_audits')
            ->where('customer_sistapp_id', $tenantId)
            ->select('entity')
            ->distinct()
            ->orderBy('entity')
            ->pluck('entity')
            ->values();

        return response()->json([
            'data' => $normalized->map(function ($x) use ($ACTION_LABEL, $ENTITY_LABEL, $customFieldsById, $recurrencesById) {
                $entity = $x->_entity_norm;
                $entityId = $x->_entity_id_norm;
                $action = $x->_action_norm;

                $before = $this->decodeJson($x->before);
                $after  = $this->decodeJson($x->after);

                $entityTitle = null;
                $entitySubtitle = null;

                if ($entity === 'custom_field' && $entityId && isset($customFieldsById[$entityId])) {
                    $f = $customFieldsById[$entityId];
                    $entityTitle = (string) $f->name;
                    $entitySubtitle = ($f->type === 'deduct' ? 'Descontar' : 'Acrescentar') . ($f->active ? '' : ' • Inativo');
                }

                if ($entity === 'recurrence' && $entityId && isset($recurrencesById[$entityId])) {
                    $rec = $recurrencesById[$entityId];
                    $entityTitle = (string) $rec->description;

                    $n = (int) ($rec->recurrence_number ?? 0);
                    $t = $rec->times !== null ? (int) $rec->times : null;

                    $part = $t ? "Parcela {$n}/{$t}" : "Parcela {$n}";
                    $due  = $rec->due_date ? (string) $rec->due_date : null;
                    $entitySubtitle = $due ? "{$part} • Venc. {$due}" : $part;
                }

                return [
                    'id' => (string) $x->id,
                    'created_at' => (string) $x->created_at,
                    'user' => [
                        'id' => (string) $x->user_id,
                        'name' => (string) $x->user_name,
                    ],
                    'entity' => $entity,
                    'entity_id' => $entityId,
                    'entity_label' => $ENTITY_LABEL[$entity] ?? $entity,
                    'entity_title' => $entityTitle,
                    'entity_subtitle' => $entitySubtitle,

                    'action' => $action,
                    'action_label' => $ACTION_LABEL[$action] ?? $action,

                    'before' => $before,
                    'after'  => $after,
                ];
            })->values(),
            'meta' => [
                'page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
            'filters' => [
                'users' => $users,
                'actions' => $actionsRaw->map(fn($a) => [
                    'id' => $a,
                    'name' => $ACTION_LABEL[$a] ?? $a,
                ])->values(),
                'entities' => $entitiesRaw->map(fn($e) => [
                    'id' => $e,
                    'name' => $ENTITY_LABEL[$e] ?? $e,
                ])->values(),
            ],
        ]);
    }
}
