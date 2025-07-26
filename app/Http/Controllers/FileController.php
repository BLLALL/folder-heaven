<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    public function store(StoreFileRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            if ($request->hasFile('file')) {
                $uploadedFile = $request->file('file');

                $path = $uploadedFile->store('files/'.auth()->id());
                $data['path'] = $path;
                $data['size'] = $uploadedFile->getSize();
                $data['mime_type'] = $uploadedFile->getMimeType();
                $data['name'] = $data['name'] ?? $uploadedFile->getClientOriginalName();
            }

            $data['owner_id'] = auth()->id();
            $file = File::create($data);

            DB::commit();

            return new FileResource($file->load('owner'));

        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }

            return response()->json([
                'message' => 'Failed to create file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
