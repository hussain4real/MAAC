<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RunMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StartRunRequest;
use App\Jobs\ProcessAgentRun;
use App\Support\Governance\IncidentGuard;
use App\Support\Governance\QuotaGuard;
use App\Support\Runtime\AgentRunner;
use App\Support\Runtime\RunAuthorizer;
use App\Support\Runtime\RunPayload;
use App\Support\Sdk\SdkContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The runtime invocation API: a registered application starts an agent run and
 * reads its status. Authentication and the application context are resolved by
 * the `sdk.auth` middleware; ownership and publication are enforced by the
 * {@see RunAuthorizer}.
 */
class AgentRunController extends Controller
{
    /**
     * Start a run for the given published agent. A synchronous run blocks until
     * it reaches a boundary and is returned `201`; an asynchronous run is queued
     * for a worker and returned `202`, to be observed via polling, streaming, or
     * a webhook.
     */
    public function store(StartRunRequest $request, RunAuthorizer $authorizer, IncidentGuard $incidents, QuotaGuard $quota, AgentRunner $runner, string $agentSlug): JsonResponse
    {
        $context = SdkContext::fromRequest($request);
        $agent = $authorizer->resolveAgent($context->application, $agentSlug);

        $incidents->assert($context->application);
        $quota->assert($context->application, $agent, $context->environment);

        if ($request->mode() === RunMode::Async) {
            $run = $runner->createRun($agent, $context->application, $context->environment, $request->runInput(), $request->caller(), RunMode::Async);
            ProcessAgentRun::dispatch($run);

            return new JsonResponse(RunPayload::for($run), 202);
        }

        $run = $runner->start($agent, $context->application, $context->environment, $request->runInput(), $request->caller());

        return new JsonResponse(RunPayload::for($run), 201);
    }

    /**
     * Return the current status of a run owned by the application.
     */
    public function show(Request $request, RunAuthorizer $authorizer, AgentRunner $runner, string $runId): JsonResponse
    {
        $context = SdkContext::fromRequest($request);
        $run = $runner->refreshExpiry($authorizer->resolveRun($context->application, $runId));

        return new JsonResponse(RunPayload::for($run));
    }
}
