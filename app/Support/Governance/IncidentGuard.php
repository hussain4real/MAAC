<?php

namespace App\Support\Governance;

use App\Exceptions\Sdk\RuntimeRequestException;
use App\Models\Application;

/**
 * Enforces a break-glass application freeze at the runtime boundary: while an
 * application is frozen, new runs and in-flight runs are rejected with a
 * controlled {@see RuntimeRequestException} so an operator can contain an
 * incident without partial execution.
 */
class IncidentGuard
{
    /**
     * Reject the call when the application's runtime is frozen.
     *
     * @throws RuntimeRequestException
     */
    public function assert(Application $application): void
    {
        if ($application->isRuntimeFrozen()) {
            throw RuntimeRequestException::runtimeFrozen();
        }
    }
}
