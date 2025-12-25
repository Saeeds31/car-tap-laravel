<?php

namespace Modules\Cars\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CarsStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'            => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string'],
            'price'            => ['required', 'integer', 'min:0'],
            'image'       => ['required', 'file', 'max:1024'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
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
