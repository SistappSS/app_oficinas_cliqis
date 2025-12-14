<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

trait CrudResponse
{
    use HttpResponse, RoleCheckTrait;

    public function indexMethod($model, string ...$params)
    {
        $model->each(function ($model) use ($params) {
            foreach ($params as $param) {
                $model->$param;
            }

            if ($model->price) $model->brlPrice = brlPrice($model->price);
            if ($model->total_budget_price) $model->brlSubTotal = brlPrice($model->total_budget_price);
            if ($model->total_price) $model->brlTotal = brlPrice($model->total_price);

            $model->humansDate = humansDate($model->created_at);
        });

        return $this->trait("get", $model);
    }

    public function storeMethod($model, $data)
    {
        if(isset($data['price'])) { $data['price'] = str_replace(',', '.', $data['price']); }

        //$data['user_id'] = $this->userAuth();
        $data['customer_sistapp_id'] = $this->customerSistappID() ?? $this->employeeSistappID();
        $data['created_at'] = Carbon::now();

        return $this->trait("store", $model->create($data));
    }

    public function showMethod($model, string ...$params)
    {
        if ($model === null) {
            return $this->trait("error");
        } else {
            foreach ($params as $param) {
                $model->$param;
            }

            return $this->trait("get", $model);
        }
    }

    public function updateMethod($model, $data)
    {
        $data['updated_at'] = Carbon::now();

        return $this->trait("update", $model->update($data));
    }

    public function destroyMethod($model, string ...$params)
    {
        if ($model === null) {
            return $this->trait("error");
        }

        foreach ($params as $param) {
            $relation = $model->$param();

            if ($relation instanceof HasManyThrough ||
                $relation instanceof HasMany) {
                $relation->get()->each->delete();
            } elseif ($relation instanceof BelongsTo ||
                $relation instanceof HasOne) {
                optional($relation->first())->delete();
            }
        }

        $model->delete();

        return $this->trait("delete", $model);
    }
}
