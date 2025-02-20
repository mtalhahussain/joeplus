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
            'project_id' => 'nullable|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'email' => 'required_without:data|email',
            'role' => 'nullable',
            'data.*' => 'nullable|array',
        ]);

        $token = Str::random(32);
        if(count($request->data) > 0){

            foreach ($request->data as $key => $data) {

                $invitation = Invitation::create([
                    'email' => $data['email'],
                    'project_id' => $data['project_id'] ?? null,
                    'task_id' => $data['task_id'] ?? null,
                    'token' => $token,
                    'role' => $data['role'] ?? null,
                    'status' => 'pending',
                ]);
        
                Mail::to($data['email'])->send(new InviteMail($invitation));
            }

        }else{

            $invitation = Invitation::create([
                'email' => $request->email,
                'project_id' => $request->project_id,
                'task_id' => $request->task_id,
                'token' => $token,
                'role' => $request->role,
                'status' => 'pending',
            ]);
    
            Mail::to($request->email)->send(new InviteMail($invitation));
        }

        return $this->successResponse([], 'Invitation sent successfully');
    }

 
    public function acceptInvite($token)
    {
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation || $invitation->status !== 'pending') return $this->errorResponse([], 'Invalid or expired invitation', 422);
        
        $user = User::where('email', $invitation->email)->first();

        if (!$user) return $this->successResponse(['email' => $invitation->email, 'token' => $token], 'Please register to accept the invitation',422);
        
        if ($invitation->project_id) {
            $role = $invitation->role ?? 'viewer';
            $user->projects()->attach($invitation->project_id, ['role' => $role]);
        } elseif ($invitation->task_id) {
            Task::find($invitation->task_id)->assignees()->attach($user->id);
        }

        $invitation->update(['status' => 'accepted']);

        return $this->successResponse([], 'You have joined the project/task!');
    }

    public function Demo()
    {
        $data = [
            [
                'name' => 'John Doe',
                'image' => 'https://api.dicebear.com/9.x/personas/svg',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui.',
            ],
            [
                'name' => 'Jane Doe',
                'image' => 'https://api.dicebear.com/9.x/personas/svg',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui.',
            ],
            [
                'name' => 'John Doe',
                'image' => 'https://api.dicebear.com/9.x/personas/svg',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui.',
            ],
            [
                'name' => 'Jane Doe',
                'image' => 'https://api.dicebear.com/9.x/personas/svg?KO',
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui. Nulla nec purus feugiat, molestie ipsum et, consequat nibh. Etiam non elit dui.',
            ],
           
        ];
       
        return $this->successResponse($data, 'Demo data fetched successfully',200);
    }

}
