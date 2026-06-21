/* ============================================================
   MAAC — Tool contract form (Phase 7)
   Shared create/edit modal for tool contracts, used by the Tool
   Registry list and the tool detail page. Includes a small
   key→type schema editor that builds the `field => '<base>[?]
   [·format]'` map the Store/UpdateToolContractRequest validate.
   ============================================================ */
import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    store as storeTool,
    update as updateTool,
} from '@/actions/App/Http/Controllers/Maac/ToolContractController';
import {
    Btn,
    Field,
    Input,
    Modal,
    Select,
    Textarea,
    Toggle,
} from '@/components/maac/ui';
import type { Tool } from '@/maac/data';
import {
    EXEC_MODE_OPTIONS,
    FieldError,
    SENSITIVITY_OPTIONS,
    TOOL_SCOPE_OPTIONS,
    toEnumValue,
    useCurrentTeam,
} from '@/maac/forms';
import { useMaacData } from '@/maac/use-data';

type SchemaRow = { key: string; type: string };

/** Sentinel select value for "no owning application" (Radix disallows ''). */
const NO_APP = 'none';

function objToRows(obj: Record<string, string>): SchemaRow[] {
    const rows = Object.entries(obj).map(([key, type]) => ({ key, type }));

    return rows.length ? rows : [{ key: '', type: 'string' }];
}

function rowsToObj(rows: SchemaRow[]): Record<string, string> {
    return rows.reduce<Record<string, string>>((acc, row) => {
        const key = row.key.trim();

        if (key) {
            acc[key] = row.type.trim() || 'string';
        }

        return acc;
    }, {});
}

const parseTimeout = (value: string): number => parseInt(value, 10) || 15;

const parsePayloadKb = (value: string): number => {
    const [amount, unit] = value.split(' ');
    const num = parseInt(amount, 10) || 256;

    return unit === 'MB' ? num * 1024 : num;
};

function SchemaEditor({
    rows,
    onChange,
}: {
    rows: SchemaRow[];
    onChange: (rows: SchemaRow[]) => void;
}) {
    const update = (index: number, patch: Partial<SchemaRow>) =>
        onChange(
            rows.map((row, i) => (i === index ? { ...row, ...patch } : row)),
        );

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            {rows.map((row, index) => (
                <div key={index} style={{ display: 'flex', gap: 8 }}>
                    <Input
                        value={row.key}
                        onChange={(e) => update(index, { key: e.target.value })}
                        placeholder="field_name"
                        style={{ fontFamily: 'var(--mono)' }}
                    />
                    <Input
                        value={row.type}
                        onChange={(e) =>
                            update(index, { type: e.target.value })
                        }
                        placeholder="string"
                        style={{ fontFamily: 'var(--mono)', width: 170 }}
                    />
                    <Btn
                        variant="ghost"
                        size="icon"
                        icon="trash"
                        style={{ height: 36, width: 36, flexShrink: 0 }}
                        onClick={() =>
                            onChange(rows.filter((_, i) => i !== index))
                        }
                    />
                </div>
            ))}
            <Btn
                variant="soft"
                size="sm"
                icon="plus"
                onClick={() => onChange([...rows, { key: '', type: 'string' }])}
            >
                Add field
            </Btn>
        </div>
    );
}

export function ToolFormModal({
    tool,
    open,
    onClose,
}: {
    tool?: Tool;
    open: boolean;
    onClose: () => void;
}) {
    const team = useCurrentTeam();
    const MAAC = useMaacData();
    const isEdit = !!tool;
    const [inputRows, setInputRows] = useState<SchemaRow[]>(
        tool ? objToRows(tool.input) : [{ key: '', type: 'string' }],
    );
    const [outputRows, setOutputRows] = useState<SchemaRow[]>(
        tool ? objToRows(tool.output) : [{ key: '', type: 'string' }],
    );

    const form = useForm<{
        name: string;
        application_id: string;
        description: string;
        scope: string;
        execution_mode: string;
        sensitivity: string;
        requires_approval: boolean;
        timeout_seconds: number;
        max_payload_kb: number;
        version: string;
        input_schema: Record<string, string>;
        output_schema: Record<string, string>;
    }>({
        name: tool?.name ?? '',
        application_id: tool?.appId
            ? (MAAC.appById(tool.appId)?.uuid ?? NO_APP)
            : NO_APP,
        description: tool?.desc ?? '',
        scope: tool ? toEnumValue(tool.scope) : 'project',
        execution_mode: tool ? tool.execMode : 'client',
        sensitivity: tool ? toEnumValue(tool.sensitivity) : 'internal',
        requires_approval: tool ? tool.approval : false,
        timeout_seconds: tool ? parseTimeout(tool.timeout) : 15,
        max_payload_kb: tool ? parsePayloadKb(tool.maxPayload) : 256,
        version: tool?.version ?? '1.0.0',
        input_schema: tool ? tool.input : {},
        output_schema: tool ? tool.output : {},
    });

    const close = () => {
        form.clearErrors();
        onClose();
    };

    const submit = () => {
        if (!team) {
            return;
        }

        form.transform((data) => ({
            ...data,
            application_id:
                data.application_id === NO_APP ? '' : data.application_id,
            input_schema: rowsToObj(inputRows),
            output_schema: rowsToObj(outputRows),
        }));

        if (tool) {
            form.put(updateTool([team.slug, tool.id]).url, {
                preserveScroll: true,
                onSuccess: () => onClose(),
            });

            return;
        }

        form.post(storeTool([team.slug]).url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    const appOptions = [
        { value: NO_APP, label: 'None (global / platform tool)' },
        ...MAAC.apps.map((app) => ({
            value: app.uuid ?? app.id,
            label: app.name,
        })),
    ];
    const half = { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 14 };
    const third = {
        display: 'grid',
        gridTemplateColumns: '1fr 1fr 1fr',
        gap: 14,
    };

    return (
        <Modal
            open={open}
            onClose={close}
            icon="tools"
            title={isEdit ? 'Edit tool contract' : 'Create Tool Contract'}
            sub="MAAC owns the contract; execution lives where the mode specifies."
            width={640}
            footer={
                <>
                    <Btn variant="ghost" onClick={close}>
                        Cancel
                    </Btn>
                    <Btn
                        variant="primary"
                        icon="check"
                        disabled={form.processing}
                        onClick={submit}
                    >
                        {isEdit ? 'Save changes' : 'Create Contract'}
                    </Btn>
                </>
            }
        >
            <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                <Field
                    label="Tool name"
                    required
                    hint="camelCase identifier the agent will call"
                >
                    <Input
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="getBusinessData"
                        style={{ fontFamily: 'var(--mono)' }}
                    />
                    <FieldError error={form.errors.name} />
                </Field>
                <Field label="Description">
                    <Textarea
                        rows={2}
                        value={form.data.description}
                        onChange={(e) =>
                            form.setData('description', e.target.value)
                        }
                        placeholder="What does this tool retrieve or do?"
                    />
                    <FieldError error={form.errors.description} />
                </Field>
                <div style={half}>
                    <Field label="Scope" required>
                        <Select
                            value={form.data.scope}
                            onChange={(v) => form.setData('scope', v)}
                            options={TOOL_SCOPE_OPTIONS}
                        />
                        <FieldError error={form.errors.scope} />
                    </Field>
                    <Field label="Execution mode" required>
                        <Select
                            value={form.data.execution_mode}
                            onChange={(v) => form.setData('execution_mode', v)}
                            options={EXEC_MODE_OPTIONS}
                        />
                        <FieldError error={form.errors.execution_mode} />
                    </Field>
                </div>
                <div style={half}>
                    <Field label="Data sensitivity" required>
                        <Select
                            value={form.data.sensitivity}
                            onChange={(v) => form.setData('sensitivity', v)}
                            options={SENSITIVITY_OPTIONS}
                        />
                        <FieldError error={form.errors.sensitivity} />
                    </Field>
                    {!isEdit && (
                        <Field
                            label="Owning application"
                            hint="Client-side tools belong to one application."
                        >
                            <Select
                                value={form.data.application_id}
                                onChange={(v) =>
                                    form.setData('application_id', v)
                                }
                                options={appOptions}
                            />
                            <FieldError error={form.errors.application_id} />
                        </Field>
                    )}
                </div>
                <div style={third}>
                    <Field label="Timeout (s)" required>
                        <Input
                            type="number"
                            min="1"
                            max="600"
                            value={form.data.timeout_seconds}
                            onChange={(e) =>
                                form.setData(
                                    'timeout_seconds',
                                    parseInt(e.target.value, 10) || 0,
                                )
                            }
                        />
                        <FieldError error={form.errors.timeout_seconds} />
                    </Field>
                    <Field label="Max payload (KB)" required>
                        <Input
                            type="number"
                            min="1"
                            max="10240"
                            value={form.data.max_payload_kb}
                            onChange={(e) =>
                                form.setData(
                                    'max_payload_kb',
                                    parseInt(e.target.value, 10) || 0,
                                )
                            }
                        />
                        <FieldError error={form.errors.max_payload_kb} />
                    </Field>
                    <Field label="Version">
                        <Input
                            value={form.data.version}
                            onChange={(e) =>
                                form.setData('version', e.target.value)
                            }
                            placeholder="1.0.0"
                            style={{ fontFamily: 'var(--mono)' }}
                        />
                        <FieldError error={form.errors.version} />
                    </Field>
                </div>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 14,
                        padding: '11px 0',
                    }}
                >
                    <div style={{ flex: 1 }}>
                        <div style={{ fontSize: 13, fontWeight: 600 }}>
                            Requires approval
                        </div>
                        <div style={{ fontSize: 12, color: 'var(--text-3)' }}>
                            Gate this tool behind a governance approval before
                            production use.
                        </div>
                    </div>
                    <Toggle
                        on={form.data.requires_approval}
                        onChange={(v) => form.setData('requires_approval', v)}
                    />
                </div>
                <Field
                    label="Input schema"
                    required
                    hint="Field → type. Base types: string, number, integer, boolean, object, array. Add ? for optional, ·format for a format (e.g. string·date)."
                >
                    <SchemaEditor rows={inputRows} onChange={setInputRows} />
                    <FieldError error={form.errors.input_schema} />
                </Field>
                <Field label="Output schema" required>
                    <SchemaEditor rows={outputRows} onChange={setOutputRows} />
                    <FieldError error={form.errors.output_schema} />
                </Field>
            </div>
        </Modal>
    );
}
