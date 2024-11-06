<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Storage;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function successResponse($data, $message = 'Success', $code = 200)
    {
        return response()->json(['message' => $message,'data' => $data], $code);
    }

    protected function errorResponse($data = [] ,$message = 'Error', $code = 422)
    {
        return response()->json(['message' => $message, 'data' => $data], $code);
    }

    protected function uploadFile($file, $type, $location = null)
    {
        if(!$location) $path = 'attachments/'. $type;
        else $path = $location . '/' . $type;
        $filename = time() .rand(112,2). '.' . $file->getClientOriginalExtension();
        
        if(Storage::disk('public')->exists($path) && \Str::contains($path, 'users')) Storage::disk('public')->deleteDirectory($path);
        Storage::disk('public')->putFileAs($path, $file, $filename);
        
        return ['path' => $path.'/'.$filename , 'filename' => $filename];
    }

    protected function deleteFile($path)
    {
        if(Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path);
    }

}
