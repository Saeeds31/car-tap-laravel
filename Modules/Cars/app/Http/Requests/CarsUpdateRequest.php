<?php

namespace Modules\Cars\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarsUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'            => ['sometimes', 'string', 'max:255'],
            'description'      => ['sometimes', 'string'],
            'price'            => ['sometimes', 'integer', 'min:0'],
            'image'       => ['sometimes', 'max:1024'],
            'brand_id' => ['sometimes', 'integer', 'exists:brands,id'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
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
