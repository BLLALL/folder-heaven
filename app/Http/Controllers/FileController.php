<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                if ($data['parent_folder_id']) {
                    $destination = File::find($data['parent_folder_id']);

                    if (! $destination || ! $destination->is_folder) {
                        return response()->json(['message' => 'parent must be a folder'], 422);
                    }

                    $this->authorize($destination);

                    if ($destination->path != Str::beforeLast($data['path'], '/')) {
                        return response()->json(['message' => 'path doesn\'t align with parent folder\'s path']);
                    }
                  
                    $uploadedFile->storeAs(Str::beforeLast($data['path'], '/'), Str::afterLast($data['path'], '/'));
                }
            }

            $data['size'] = $uploadedFile->getSize();
            $data['mime_type'] = $uploadedFile->getMimeType();
            $data['name'] = $data['name'] ?? $uploadedFile->getClientOriginalName();
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

    public function show(File $file)
    {
        $this->authorize($file);

        return new FileResource($file->load(['owner', 'children']));
    }

    public function update(UpdateFileRequest $request, File $file)
    {
        $this->authorize(folder: $file);

        $data = $request->validated();

        $file->update($data);

        return new FileResource($file);
    }

    public function destroy(File $file)
    {
        $this->authorize($file);

        if(!Storage::delete($file->path)) {
            return response()->json('Failed to delete file with its current path');
        }


        $file->delete();


        return response()->json(['message' => 'File deleted successfully']);

    }

    public function download(File $file)
    {
        $this->authorize($file);

        if ($file->is_folder) {
            return response()->json(['message' => 'Cannot download folders'], 422);
        }

        if (! $file->path || ! Storage::exists($file->path)) {
            return response()->json(['message' => 'File not found'], 404);
        }
        return Storage::download($file->path, $file->name);
    }
}
