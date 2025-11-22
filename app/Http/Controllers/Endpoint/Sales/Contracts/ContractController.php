<?php

namespace App\Http\Controllers\Endpoint\Sales\Contracts;

use App\Http\Controllers\Controller;

use App\Http\Requests\Sales\Contracts\StoreContractRequest;
use App\Http\Requests\Sales\Contracts\UpdateContractRequest;

use App\Models\Sales\Contracts\Contract;
use App\Traits\CrudResponse;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    use CrudResponse;

    protected $contract;

    public function __construct(Contract $contract) {
        $this->contract = $contract;
    }

    public function index()
    {
        return $this->indexMethod($this->contract->with('customer')->get());
    }

    public function store(StoreContractRequest $request)
    {
        $request->validated();

        $data = [
            'name' => $request->name,
            'customer_id' => $request->customer_id,
            'contract' => $request->contract_content,
            'start_contract_date' => $request->start,
            'end_contract_date' => $request->end,
        ];

        return $this->storeMethod($this->contract, $data);
    }

    public function show(Contract $contract)
    {
        //
    }

    public function update(UpdateContractRequest $request, Contract $contract)
    {
        //
    }

    public function destroy($id)
    {
        return $this->destroyMethod($this->contract->find($id));
    }
}
