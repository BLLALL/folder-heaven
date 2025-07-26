<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
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
            'file' => ['required_without:is_folder', 'file', 'max:102400'], // max: 100MB
            'name' => ['nullable', 'string', 'max:255'],
            'parent_folder_id' => 'nullable|exists:files,id',
            'is_folder' => 'nullable|boolean',

        ];
    }

    public function messages()
    {
        return [
            'file.max' => 'File size cannot exceed 100MB',
            'parent_folder_id.exists' => 'Parent folder does not exist',
        ];
    }
}
