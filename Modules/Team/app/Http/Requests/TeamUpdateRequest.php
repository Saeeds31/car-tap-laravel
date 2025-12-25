<?php

namespace Modules\Team\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'full_name'     => ['sometimes', 'string', 'max:255'],
            'image'     => ['sometimes', 'file', 'max:1024'],
            'post'     => ['sometimes', 'string', 'max:255'],
            'sort_order'     => ['sometimes', 'integer'],

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
