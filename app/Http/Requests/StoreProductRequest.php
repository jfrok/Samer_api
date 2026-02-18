<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Handle authorization in middleware/policy
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',
            'description' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string|max:255',
            'base_price' => 'required|numeric|min:0|max:999999999.99',
            'is_active' => 'boolean',

            // Product gallery images - actual file uploads
            'gallery' => 'nullable|array|max:10',
            'gallery.*' => [
                'required',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:5120', // 5MB
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000'
            ],

            // Variants
            'variants' => 'nullable|array',
            'variants.*.size' => 'required|string|max:50',
            'variants.*.color' => 'required|string|max:50',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.sku' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'gallery.*.image' => 'Each file must be an image.',
            'gallery.*.mimes' => 'Only JPEG, PNG, GIF, and WebP images are allowed.',
            'gallery.*.max' => 'Each image must be less than 5MB.',
            'gallery.*.dimensions' => 'Images must be between 100x100 and 4000x4000 pixels.',
            'gallery.max' => 'You can upload a maximum of 10 images.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Generate slug from name if not provided
        if (!$this->slug && $this->name) {
            $this->merge([
                'slug' => \Str::slug($this->name)
            ]);
        }
    }
}
