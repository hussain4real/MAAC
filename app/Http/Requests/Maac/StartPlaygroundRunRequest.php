<?php

namespace App\Http\Requests\Maac;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a console playground run request. Authorization is enforced in the
 * controller via the agent policy once the target agent is resolved.
 */
class StartPlaygroundRunRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'input' => ['required', 'string', 'max:8000'],
            'caller' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The user prompt the agent should run against.
     */
    public function runInput(): string
    {
        return (string) $this->validated('input');
    }

    /**
     * The caller label recorded against the run, defaulting to the console user.
     */
    public function caller(): string
    {
        $caller = $this->validated('caller');

        if (is_string($caller) && $caller !== '') {
            return $caller;
        }

        return 'console:'.$this->user()->email;
    }
}
