<?php

use App\Enums\RunMode;
use App\Enums\WebhookDeliveryStatus;
use App\Enums\WebhookEndpointStatus;
use App\Enums\WebhookEventType;

it('describes run modes', function () {
    expect(RunMode::Sync->label())->toBe('Sync')
        ->and(RunMode::Async->label())->toBe('Async')
        ->and(RunMode::Async->isAsync())->toBeTrue()
        ->and(RunMode::Sync->isAsync())->toBeFalse()
        ->and(RunMode::values())->toBe(['sync', 'async'])
        ->and(RunMode::options())->toHaveCount(2);
});

it('labels every webhook event type', function () {
    foreach (WebhookEventType::cases() as $case) {
        expect($case->label())->not->toBe('');
    }

    expect(WebhookEventType::RunToolRequested->label())->toBe('Tool requested')
        ->and(WebhookEventType::RunCompleted->label())->toBe('Run completed')
        ->and(WebhookEventType::values())->toContain('run.completed', 'run.tool_requested')
        ->and(WebhookEventType::options())->toHaveCount(6);
});

it('describes webhook endpoint statuses', function () {
    expect(WebhookEndpointStatus::Active->label())->toBe('Active')
        ->and(WebhookEndpointStatus::Active->isActive())->toBeTrue()
        ->and(WebhookEndpointStatus::Disabled->isActive())->toBeFalse()
        ->and(WebhookEndpointStatus::options())->toHaveCount(2);
});

it('describes webhook delivery statuses', function () {
    expect(WebhookDeliveryStatus::Failed->label())->toBe('Failed')
        ->and(WebhookDeliveryStatus::Failed->isReplayable())->toBeTrue()
        ->and(WebhookDeliveryStatus::Delivered->isReplayable())->toBeFalse()
        ->and(WebhookDeliveryStatus::Pending->isReplayable())->toBeFalse();
});
