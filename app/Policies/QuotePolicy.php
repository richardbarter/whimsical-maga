<?php

namespace App\Policies;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuotePolicy
{
    /**
     * Published quotes are public; drafts and pending quotes are admin-only previews.
     *
     * The nullable user lets guests reach this check. Everyone else gets a 404 rather
     * than a 403, so the page doesn't reveal that an unpublished quote exists.
     */
    public function view(?User $user, Quote $quote): Response
    {
        return $quote->status === QuoteStatus::Published || $user?->isAdmin()
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
