<?php

namespace App\Http\Controllers\APP\Sales\Contracts;

use App\Http\Controllers\Controller;
use App\Models\Sales\Contracts\Contract;
use App\Traits\WebIndex;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ContractController extends Controller
{
    use WebIndex;

    public function index()
    {
        return $this->webRoute('app.sales.contract.contract_index', 'contract');
    }

    public function create()
    {
        return view('app.sales.contract.contract_create');
    }

    public function show($contractId)
    {
        $contract = Contract::with('customer')->where('id', $contractId)->first();

        Carbon::setLocale('pt_BR');
        $date = Carbon::now()->isoFormat('D [de] MMMM [de] YYYY');

        $data = [
            'name' => $contract->name,
            'contract' => $contract->contract,
            'start' => $contract->start_contract_date,
            'end' => $contract->end_contract_date,
            'customer' => upperText($contract->customer->name),
        ];

        $pdf = PDF::loadView('layouts.templates.mail.contract_pdf', $data);
        $pdf->setPaper('A4', 'retrait');

        $pdfName = ucwords($contract->name) .' - '. $contract->customer->name.'.pdf';

        return $pdf->stream($pdfName);
    }
}
