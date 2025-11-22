<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TableHeader extends Component
{
    public $title;
    public $current;

    public function __construct($title, $current)
    {
        $this->title = $title;
        $this->current = $current;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('layouts.components.table-header');
    }
}
