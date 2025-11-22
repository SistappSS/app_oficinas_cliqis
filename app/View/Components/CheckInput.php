<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CheckInput extends Component
{
    public $id;
    public $name;
    public $label;
    public $type;
    public $check;
    public $col;
    public $set;

    public function __construct($id, $name, $label, $type, $check = null, $col = null, $set = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->check = $check;
        $this->col = $col;
        $this->set = $set;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.components.check-input');
    }
}
