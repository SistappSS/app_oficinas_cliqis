<?php

namespace App\Http\Controllers\APP\Sales\Budgets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\Budgets\BudgetConfig\StoreBudgetConfigRequest;
use App\Models\Sales\Budgets\BudgetConfig;
use App\Traits\RoleCheckTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BudgetConfigController extends Controller
{
    use RoleCheckTrait;

    public function index()
    {
        $cfg = BudgetConfig::where('customer_sistapp_id', $this->customerSistappID())->first();

        return view('app.sales.budgets.budget_config.budget_config_index', [
            'cfg'      => $cfg,
            'org'      => $cfg->org ?? [],
            'rep'      => $cfg->representative ?? [],
            'texts'    => $cfg->texts ?? [],
            'logo'     => $cfg->logo['data'] ?? null,
            'logoH'    => $cfg->logo['max_height'] ?? 60,
        ]);
    }

    public function store(StoreBudgetConfigRequest $request)
    {
        $data = $request->validated();

        // se veio arquivo, converte para data-uri base64
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $base64 = base64_encode(file_get_contents($file->getRealPath()));
            $mime = $file->getMimeType() ?: 'image/png';
            $data['logo']['data'] = "data:{$mime};base64,{$base64}";
            $data['logo']['mime'] = $mime;
            $data['logo']['max_height'] = $data['logo']['max_height'] ?? 60;
        } else {
            // se veio base64 cru, vira data-uri
            if (!empty($data['logo']['data']) && !Str::startsWith($data['logo']['data'], 'data:image')) {
                $mime = $data['logo']['mime'] ?? 'image/png';
                $data['logo']['data'] = "data:{$mime};base64,{$data['logo']['data']}";
            }
        }

        $userId = auth()->id();

        $sistappId = $this->customerSistappID();

        $cfg = BudgetConfig::updateOrCreate(
            ['customer_sistapp_id' => $sistappId],
            [
                'user_id'        => $userId,
                'org'            => $data['org'],
                'representative' => $data['representative'] ?? null,
                'texts'          => $data['texts'] ?? null,
                'logo'           => $data['logo'] ?? null,
            ]
        );

        return redirect()
            ->back()
            ->with('success', 'Configurações salvas.')
            ->withInput();
    }
}
