<?php

namespace App\Enums;

use Illuminate\Support\Str;

/**
 * The terminal state of a webhook delivery attempt: still pending/retrying,
 * delivered successfully (2xx), or failed after exhausting its attempts.
 */
enum WebhookDeliveryStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Failed = 'failed';

    /**
     * Get the human-readable label for the delivery status.
     */
    public function label(): string
    {
        return Str::headline($this->value);
    }

    /**
     * Whether a delivery in this state can be replayed (re-dispatched).
     */
    public function isReplayable(): bool
    {
        return $this === self::Failed;
    }
}
