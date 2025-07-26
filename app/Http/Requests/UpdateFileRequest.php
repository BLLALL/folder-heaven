// app/Http/Requests/UpdateFileRequest.php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'parent_folder_id' => 'sometimes|nullable|exists:files,id',
        ];
    }
}
