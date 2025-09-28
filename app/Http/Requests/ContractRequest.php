<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:full-time,part-time,contractor',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'renewal_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:active,inactive',
            'company_id' => 'required|exists:companies,id',
        ];
    }
}
