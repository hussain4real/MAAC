<?php

namespace App\Enums;

use App\Models\IncidentAction;

/**
 * The break-glass / incident-response controls an operator can trigger to
 * immediately contain an incident, recorded as an {@see IncidentAction}: revoke a
 * compromised credential, disable a model, shut down a connector, suspend a
 * webhook, or freeze an application's runtime (and lift that freeze). These
 * bypass normal approval — that is the point — but every action is audited.
 */
enum IncidentActionType: string
{
    case RevokeCredential = 'revoke_credential';
    case DisableModel = 'disable_model';
    case ShutdownConnector = 'shutdown_connector';
    case SuspendWebhook = 'suspend_webhook';
    case FreezeApplication = 'freeze_application';
    case LiftFreeze = 'lift_freeze';

    /**
     * Get the display label for the action (e.g. "Revoke Credential").
     */
    public function label(): string
    {
        return match ($this) {
            self::RevokeCredential => 'Revoke Credential',
            self::DisableModel => 'Disable Model',
            self::ShutdownConnector => 'Shut Down Connector',
            self::SuspendWebhook => 'Suspend Webhook',
            self::FreezeApplication => 'Freeze Application Runtime',
            self::LiftFreeze => 'Lift Runtime Freeze',
        };
    }

    /**
     * The alert severity an action of this type raises in the incident timeline.
     */
    public function severity(): AlertSeverity
    {
        return $this === self::LiftFreeze ? AlertSeverity::Low : AlertSeverity::High;
    }

    /**
     * Get all action types as value/label option pairs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
