<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $productId = (int) $this->route('product');
        return [
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'sku' => ['sometimes', 'required', 'string', 'max:60', Rule::unique('products', 'sku')->where('user_id', $this->user()->id)->ignore($productId)],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0', 'max:2147483647'],
            'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
