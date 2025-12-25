<?php

namespace Modules\Receipt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'image' => "required|file|max:1024",
            'amount' => "required|integer|min:1000",
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
