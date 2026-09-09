<?php

namespace App\Livewire\ActionCenter;

use App\Services\ActionCenterService;
use Livewire\Component;

class ActionCenter extends Component
{
    public string $urgency = '';

    public function render()
    {
        $service = app(ActionCenterService::class);
        $items = $service->forUser(auth()->user());

        if (filled($this->urgency)) {
            $items = $items
                ->where('urgency', $this->urgency)
                ->values();
        }

        return view('livewire.action-center.action-center', [
            'items' => $items,
            'allCount' => $service->forUser(auth()->user())->count(),
        ])->layout('layouts.app');
    }
}
