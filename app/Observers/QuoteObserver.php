<?php

namespace App\Observers;

use App\Models\Quote;

class QuoteObserver
{
    /**
     * Set published_at the first time a quote transitions to 'published'.
     *
     * Fires before every save (create or update). Only acts when:
     * - status is being set to 'published', AND
     * - published_at has not already been recorded
     *
     * This preserves the original publication date across future edits.
     */
    public function saving(Quote $quote): void
    {
        if ($quote->status === 'published' && is_null($quote->published_at)) {
            $quote->published_at = now();
        }
    }
}
