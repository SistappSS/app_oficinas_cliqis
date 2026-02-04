<?php

namespace App\Http\Controllers\Application\ServiceOrders;

use App\Http\Controllers\Controller;
use App\Models\ServiceOrders\CompletedServiceOrders\CompletedServiceOrder;
use App\Models\ServiceOrders\ServiceOrder;
use App\Traits\CrudResponse;
use App\Traits\RoleCheckTrait;
use App\Traits\WebIndex;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompletedServiceOrderController extends Controller
{
    use CrudResponse, RoleCheckTrait, WebIndex;

    protected CompletedServiceOrder $completedServiceOrder;

    public function __construct(CompletedServiceOrder $completedServiceOrder)
    {
        $this->completedServiceOrder = $completedServiceOrder;
    }

    public function view()
    {
        return $this->webRoute('app.service_orders.completed_service_order.completed_service_order_index', 'completed-service-order');
    }

    public function index(Request $request)
    {
        $q = $this->completedServiceOrder->query()
            ->with(['serviceOrder', 'technician'])
            ->orderByDesc('completed_at');

        if ($term = trim($request->input('q', ''))) {
            $q->where(function ($w) use ($term) {
                $w->where('client_name', 'like', "%{$term}%")
                    ->orWhere('client_email', 'like', "%{$term}%");
            });
        }

        $data = $q->paginate(20);

        return response()->json($data);
    }

    public function store(Request $request, string $serviceOrder)
    {
        //dd($request->all());

        // valida base64 que vem do canvas
        $data = $request->validate([
            'image_base64' => ['required', 'string'],
            'technician_id'  => ['nullable', 'string'],
            'client_name'  => ['nullable', 'string', 'max:191'],
            'client_email' => ['nullable', 'string', 'max:191'],
        ]);

        // carrega a OS
        $os = ServiceOrder::where('id', $serviceOrder)->firstOrFail();

        // quebra "data:image/png;base64,xxxx"
        $base64 = $data['image_base64'];
        if (str_starts_with($base64, 'data:image')) {
            [$meta, $base64] = explode(',', $base64, 2);
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return response()->json([
                'message' => 'invalid_base64',
            ], 422);
        }

        // monta path: {os_id}/client-signature-20251128-153000.png
        $fileName = 'client-signature-' . now()->format('Ymd-His') . '.png';
        $path     = $os->id . '/' . $fileName;

        // grava no disco "signatures"
        Storage::disk('signatures')->put($path, $binary);

        // cria/atualiza registro de conclusão
        $completed = CompletedServiceOrder::updateOrCreate(
            ['service_order_id' => $os->id],
            [
                'customer_sistapp_id'   => $os->customer_sistapp_id,
                'client_name'           => $data['client_name']  ?? $os->client_name ?? null,
                'client_email'          => $data['client_email'] ?? $os->client_email ?? null,
                'client_signature_path' => $path,
                'client_signed_at'      => now(),
                'completed_at'          => now(),
                'technician_id'         => $data['technician_id'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Assinatura salva com sucesso.',
            'data'    => $completed,
        ]);
    }
    public function show(string $id)
    {
        return $this->showMethod(
            $this->completedServiceOrder->with(['serviceOrder', 'technician'])->find($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'service_order_id'          => ['required', 'uuid'],
            'client_name'               => ['nullable', 'string', 'max:255'],
            'client_email'              => ['nullable', 'string', 'max:255'],
            'client_signature_path'     => ['nullable', 'string'],
            'client_signed_at'          => ['nullable', 'date'],
            'technician_id'             => ['nullable', 'uuid'],
            'technician_signature_path' => ['nullable', 'string'],
            'technician_signed_at'      => ['nullable', 'date'],
            'completed_at'              => ['nullable', 'date'],
        ]);

        return $this->updateMethod($this->completedServiceOrder->find($id), $validated);
    }

    public function destroy(string $id)
    {
        return $this->destroyMethod($this->completedServiceOrder->find($id));
    }

}
