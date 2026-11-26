<?php

namespace App\Http\Requests\Api;

use App\Enums\JobStatus;
use App\Models\ServiceJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** PATCH /api/jobs/{id}/status */
class UpdateJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->job()); // 404 for other users

        return true;
    }

    private function job(): ServiceJob
    {
        return $this->route('job');
    }

    /** A job can only become "scheduled" when it has a date. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->input('status') === JobStatus::Scheduled->value
                    && $this->job()->scheduled_at === null) {
                    $validator->errors()->add('status', 'This job has no scheduled date yet. Set scheduled_at first (edit the job).');
                }
            },
        ];
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(JobStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.enum' => 'The status must be one of: '.implode(', ', JobStatus::values()).'.',
        ];
    }
}
