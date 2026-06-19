<?php

namespace App\View\Components\header;

use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserDropdown extends Component
{
    public string $userName;

    public string $initials;

    public string $portal;

    public ?string $avatarSrc;

    public function __construct(
        ?string $userName = null,
        ?string $initials = null,
        ?string $portal = null,
        ?string $avatarSrc = null,
    ) {
        /** @var User|null $user */
        $user = auth()->user();

        $this->portal = $portal ?? (request()->segment(1) === 'admin' ? 'admin' : 'student');
        $this->userName = $userName ?? $user?->name ?? ucfirst($this->portal) . ' User';
        $this->initials = $initials ?? $user?->initials() ?? 'NL';
        $this->avatarSrc = $avatarSrc ?? $user?->avatarUrl();
    }

    public function render(): View|Closure|string
    {
        return view('components.header.user-dropdown');
    }
}
