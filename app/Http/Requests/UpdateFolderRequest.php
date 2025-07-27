<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFolderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'path' => 'required|string|max:255|starts_with:/',
            'parent_folder_id' => 'required|integer',
        ];
    }
}
