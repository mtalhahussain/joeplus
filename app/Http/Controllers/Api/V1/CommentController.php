<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Task, TaskComment, TemporaryImage, Attachment};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DOMDocument;

class CommentController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'comment' => 'required|string',
        ]);

        $comment = TaskComment::create([
            'user_id' => auth()->id(),
            'task_id' => $request->task_id,
            'comment' => $request->comment,
        ]);

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($request->comment);
        libxml_clear_errors();
        $imagePaths = [];
        foreach ($dom->getElementsByTagName('img') as $img) {
            $imagePaths[] = $img->getAttribute('src');
        }
      
        if(count($imagePaths) > 0){

            foreach ($imagePaths as $path) {

                $path = Str::replace("\\", '', $path);
              
                $adjusted = Str::after($path, 'storage/');
                $adjusted = rtrim($adjusted, '"');
                
                $temporaryImage = TemporaryImage::where('file_path', $adjusted)
                                                ->where('file_type', 'image')
                                                ->where('user_id', auth()->id())
                                                ->first();
                
                if ($temporaryImage) {
                  
                    $newPath = str_replace('temporary_images', 'comments', $temporaryImage->file_path);
                  
                    Storage::disk('public')->move($temporaryImage->file_path, $newPath);

                    $new_comment = str_replace($temporaryImage->file_path, $newPath, $request->comment);
                    $attachment = new Attachment;
                    $attachment->comment_id = $comment->id;
                    $attachment->task_id = $comment->task_id;
                    $attachment->user_id = auth()->id();
                    $attachment->file_url = asset('storage/'.$newPath);
                    $attachment->file_name = $temporaryImage->file_name;
                    $attachment->save();

                    $temporaryImage->delete();
                    TaskComment::find($comment->id)->update(['comment' => $new_comment]);
                }
            }

            $extImg = TemporaryImage::where('user_id', auth()->id())->where('file_type', 'image')->get();
            if(count($extImg) > 0){
                foreach ($extImg as $img) {
                    $img->delete();
                }
            }
        }

        return $this->successResponse($comment,'Comment added successfully',201);
    }

    public function show(Request $request,$id)
    {
        $perPage = isset($request->per_page) ? (int) $request->per_page : 10;
       
        $task = Task::where('uuid', $id)->first();
        if(!$task) return $this->errorResponse([],'Task not found',422);

        $comments = TaskComment::where('task_id', $task->id)->with('user:id,name,avatar')->get();

        return $this->successResponse($comments,'Comments fetched successfully',200);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'comment' => 'required|string',
        ]);

        $comment = TaskComment::where('uuid', $id)->first();
        if(!$comment) return $this->errorResponse([],'Comment not found',422);

        $comment->update([
            'comment' => $request->comment,
        ]);

        return $this->successResponse($comment,'Comment updated successfully',200);
    }

    public function destroy($id)
    {
        $comment = TaskComment::where('uuid', $id)->first();
        if(!$comment) return $this->errorResponse([],'Comment not found',422);

        $comment->delete();
        $comment->attachments()->get()->each(function($attachment){
            $actualPath = Str::after($attachment->file_url, 'storage/');
            Storage::disk('public')->delete($actualPath);
            $attachment->delete();
        });

        return $this->successResponse([],'Comment deleted successfully',200);
    }

    public function attachmentUpload(Request $request)
    {
        if($request->hasFile('image')){
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $fileName = $request->file('image')->getClientOriginalName();
            $imagePath = $request->file('image')->store('temporary_images', 'public');

            $temporaryImage = new TemporaryImage();
            $temporaryImage->user_id = auth()->id();
            $temporaryImage->file_path = $imagePath;
            $temporaryImage->file_name = $fileName;
            $temporaryImage->file_type = 'image';
            $temporaryImage->save();

            return $this->successResponse(['image_id' => $temporaryImage->id, 'image_url' => asset('storage/'. $temporaryImage->file_path)],'Image uploaded successfully',200);
        }
    }
}
