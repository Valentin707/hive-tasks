<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Component;

class ProfileSettings extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 401);

        return view('livewire.profile-settings', compact('user'));
    }
}
