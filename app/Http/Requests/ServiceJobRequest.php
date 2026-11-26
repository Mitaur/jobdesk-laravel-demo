<?php

namespace App\Http\Requests;

use App\Enums\JobStatus;
use App\Models\ServiceJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Create + update validation for jobs. Used by the web forms AND the API (POST /api/jobs).
 * The customer must belong to the logged-in user – another user's customer id is rejected.
 */
class ServiceJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        // On update: check ownership BEFORE validation, so another user's job
        // returns 404 instead of validation errors (no information leak).
        $job = $this->route('job');
        if ($job instanceof ServiceJob) {
            Gate::authorize('update', $job);
        }

        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'customer_id' => [
                'required', 'integer',
                Rule::exists('customers', 'id')->where('user_id', $this->user()->id),
            ],
            'title' => ['required', 'string', 'max:150'],
            'status' => ['required', Rule::enum(JobStatus::class)],
            'price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'scheduled_at' => [
                'nullable', 'date',
                // A job that is "scheduled" needs a date.
                Rule::requiredIf(fn () => $this->input('status') === JobStatus::Scheduled->value),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'customer_id.exists' => 'Please choose one of your customers.',
            'scheduled_at.required' => 'A scheduled job needs a date and time.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['customer_id' => 'customer', 'scheduled_at' => 'scheduled date'];
    }
}
