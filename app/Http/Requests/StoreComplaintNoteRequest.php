<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference_number' => 'required|exists:complaints,reference_number',
            'note'             => 'required|string|max:2000',
        ];
    }
}
