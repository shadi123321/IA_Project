<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeComplaintStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true; // لاحقاً يمكنك ربطها بالـ Policy
    }

    public function rules()
    {
        return [
            'reference_number' => 'required|exists:complaints,reference_number',
            'status'           => 'required|in:processing,new,resolved,rejected',
            'note'             => 'nullable|string',
         //   'user_id'          => 'required|exists:users,id'
        ];
      
    }
    public function messages()
    {
        return [
            'reference_number.exists' => 'complain not found',
        ];
    }
}
