<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    public $id;
    public $name;
    public $label;
    public $type;
    public $class;
    public $col;
    public $set;
    public $placeholder;
    public $disable;
    public $max;
    public $min;
    public $value;
    public $read;

    public function __construct($id, $name, $label, $type, $class = null, $col = null, $set = null, $placeholder = null, $disable = null, $max = null, $min = null, $value = null, $read = null)
    {
        $this->col = $col;
        $this->set = $set;
        $this->id = $id;
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->class = $class;
        $this->placeholder = $placeholder;
        $this->disable = $disable;
        $this->max = $max;
        $this->min = $min;
        $this->value = $value;
        $this->read = $read;
    }

    public function render(): View|Closure|string
    {
        return view('layouts.components.input');
    }
}
