<?php

namespace App\Http\Requests;

use App\Enums\DelayReasonCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SubmitDelayReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason_category' => ['required', new Enum(DelayReasonCategory::class)],
            'explanation' => ['required', 'string', 'min:10', 'max:5000'],
            'proposed_eta' => ['required', 'date', 'after:now'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
