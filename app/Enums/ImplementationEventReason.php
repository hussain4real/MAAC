<?php

namespace App\Enums;

use App\Models\ToolImplementationEvent;

/**
 * Why a {@see ToolImplementationEvent} was appended to a client-side
 * tool's implementation timeline: either the application's SDK reported a handler,
 * or a contract change forced a re-evaluation of the existing handler.
 */
enum ImplementationEventReason: string
{
    case Reported = 'reported';
    case ContractChanged = 'contract_changed';

    /**
     * Get the human-readable label for the event reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Reported by SDK',
            self::ContractChanged => 'Contract changed',
        };
    }
}
