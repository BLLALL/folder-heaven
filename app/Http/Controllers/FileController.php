<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\UpdateFileRequest;
use App\Http\Resources\FileResource;
use App\Models\File;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {

        $query = File::query()->where('owner_id', auth()->id());

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

    // public function store(StoreFileRequest $request)
    // {
    //     DB::beginTransaction();
    //     try {

    //         $data = $request->validated();

    //         if ($request->hasFile('file')) {
    //             $uploadedFile = $request->file('file');
    //             if ($data['parent_folder_id']) {
    //                 $destination = File::find($data['parent_folder_id']);

    //                 if (! $destination || ! $destination->is_folder) {
    //                     return $this->error(['message' => 'parent must be a folder'], 422);
    //                 }
    //                 $data['name'] = Str::afterLast($data['path'], '/');

    //                 $this->authorize($destination);

    //                 if ("{$destination->path}/" . $data['name'] != $data['path']) {
    //                     return $this->error(['message' => 'No match between parent folder and path!', 409]);
    //                 }

    //                 if(File::wherePath($data['path'])->exists()) {
    //                     return $this->error('File already exists!', 409);
    //                 }

    //                 $uploadedFile->storeAs(Str::beforeLast(auth()->id() . $data['path'], '/'), Str::afterLast($data['path'], '/'));
    //             }
    //         }

    //         $data['size'] = $uploadedFile->getSize();
    //         $data['mime_type'] = $uploadedFile->getMimeType();
    //         $data['owner_id'] = auth()->id();
    //         $file = File::create($data);

    //         DB::commit();

    //         return new FileResource($file->load('owner'));

    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         if (isset($path) && Storage::exists($path)) {
    //             Storage::delete($path);
    //         }

    //         return response()->json([
    //             'message' => 'Failed to create file',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

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
                        return $this->error(['message' => 'parent must be a folder'], 422);
                    }

                    $this->authorize($destination);

                    $data['path'] = $destination['path'].'/'.$data['name'];

                    if (File::wherePath($data['path'])->exists()) {
                        return $this->error('File already exists!', 409);
                    }
                    // dd($destination['path'], $data['name']);
                    File::storeFile($destination['path'], $data);
                } else {
                    return $this->error('missing or corrupted file', 422);
                }

                $data['size'] = $uploadedFile->getSize();
                $data['mime_type'] = $uploadedFile->getMimeType();
                $data['owner_id'] = auth()->id();
                $file = File::create($data);

                DB::commit();

                return new FileResource($file);
            }
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
        $this->authorize($file);

        DB::beginTransaction();

        try {
            if ($request->has('parent_folder_id')) {
                $newParentFolder = File::find($request->parent_folder_id);

                if (! $newParentFolder || ! $newParentFolder->is_folder) {
                    return $this->error(['message' => 'The destination must be a folder'], 422);
                }

                $this->authorize($newParentFolder);

                $oldPath = $file->path;

                if ($file->parent_folder_id !== $newParentFolder->id) {
                    $newPath = $newParentFolder->path.'/'.$file->name;
                    if (File::wherePath($newPath)->exists()) {
                        return $this->error('A file with this name already exists in the destination folder.', 409);
                    }

                    Storage::move(auth()->id().$file->path, auth()->id().$newPath);

                    $file->parent_folder_id = $newParentFolder->id;
                    $file->path = $newPath;
                }

            }

            if ($request->has('name') && $file->name !== $request->name) {
                $newName = $request->name;
                $newPath = dirname($file->path).'/'.$newName;

                if (File::wherePath($newPath)->exists()) {
                    return $this->error('A file with this name already exists.', 409);
                }

                Storage::move(auth()->id().$file->path, auth()->id().'/'.$newPath);

                $file->name = $newName;
                $file->path = $newPath;
            }

            $file->save();

            DB::commit();

            return new FileResource($file);
        } catch (\Exception $e) {
            DB::rollBack();

            if (Storage::exists(File::getFileRealPath($newPath))) {
                Storage::move(File::getFileRealPath($newPath), File::getFileRealPath($oldPath));
            }

            return response()->json([
                'message' => 'Failed to update file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(File $file)
    {
        $this->authorize($file);

        if (! Storage::delete($file->path)) {
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

        if (! $file->path || ! Storage::exists($file->parent->path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        // return response()->download(auth()->id()  . $file->path);
        return Storage::download(File::getFileRealPath($file->path), $file->name);
    }
}
