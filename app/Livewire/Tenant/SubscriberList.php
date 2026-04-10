<?php

namespace App\Livewire\Tenant;

use Livewire\Component;
use App\Models\Tenant\Subscriber;

class SubscriberList extends Component
{
    public string $search = '';

    public function deleteSubscriber(int $subscriberId): void
    {
        $subscriber = Subscriber::findOrFail($subscriberId);
        $subscriber->delete();

        session()->flash('success', 'Subscriber removed.');
    }

    public function render()
    {
        $query = Subscriber::with('statusPage');

        if ($this->search) {
            $query->where('email', 'like', '%' . $this->search . '%');
        }

        $subscribers = $query->orderByDesc('created_at')->paginate(20);

        return view('livewire.tenant.subscriber-list', compact('subscribers'));
    }
}
