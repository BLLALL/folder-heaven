<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use App\Http\Resources\FileResource;

class FileController extends Controller
{
    public function index(Request $request)
    {

        $query = File::query();

        if ($request->has('folder_id')) {
            $query->where('parent_folder_id', $request->folder_id);
        } else {
            $query->whereNull('parent_folder_id');
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->has('type')) {
            if ($request->type === 'folder') {
                $query->where('is_folder', true);
            } elseif ($request->type === 'file') {
                $query->where('is_folder', false);
            }
        }

        $files = $query->with('owner')
            ->orderBy('is_folder', 'desc')
            ->orderBy('name')
            ->paginate(10);

        return FileResource::collection($files);

    }
}
