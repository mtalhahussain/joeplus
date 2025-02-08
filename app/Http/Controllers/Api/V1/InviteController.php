<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Invitation, Task, User};
use Illuminate\Support\Str;
use App\Mail\InviteMail;
use Illuminate\Support\Facades\Mail;

class InviteController extends Controller
{
    public function inviteUser(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
        ]);

        $token = Str::random(32);
        
        $invitation = Invitation::create([
            'email' => $request->email,
            'project_id' => $request->project_id,
            'task_id' => $request->task_id,
            'token' => $token,
            'status' => 'pending',
        ]);

        Mail::to($request->email)->send(new InviteMail($invitation));

        return $this->successResponse([], 'Invitation sent successfully');
    }


    public function acceptInvite($token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || $invitation->status !== 'pending') return $this->errorResponse([], 'Invalid or expired invitation', 400);
        
        $user = User::where('email', $invitation->email)->first();

        if (!$user) return $this->successResponse(['email' => $invitation->email, 'token' => $token], 'Please register to accept the invitation');
        
        if ($invitation->project_id) {
            $role = $invitation->role ?? 'viewer';
            $user->projects()->attach($invitation->project_id, ['role' => $role]);
        } elseif ($invitation->task_id) {
            Task::find($invitation->task_id)->update(['user_id' => $user->id]);
        }

        $invitation->update(['status' => 'accepted']);

        // return redirect()->route('dashboard')->with('success', 'You have joined the project/task!');
        return $this->successResponse([], 'You have joined the project/task!');
    }

}
