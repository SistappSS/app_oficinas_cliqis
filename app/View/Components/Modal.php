<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Modal extends Component
{
    public $modalId;
    public $formId;
    public $input;
    public $enctype;
    public $modalTitle;

    public function __construct($modalId, $formId, $input = null, $enctype = null, $modalTitle = null)
    {
        $this->modalId = $modalId;
        $this->formId = $formId;
        $this->input = $input;
        $this->enctype = $enctype;
        $this->modalTitle = $modalTitle;
    }

    public function render(): View|Closure|string
    {
        return view('layouts.components.modal');
    }
}
