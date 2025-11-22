<?php

namespace App\Traits;

trait WebIndex
{
    public function webRoute(string $view, string $id, $collections = null)
    {
        $input = ['name' => "{$id}_id"];

        return view($view, compact('input', 'collections'));
    }
}
