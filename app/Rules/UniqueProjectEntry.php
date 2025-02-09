<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Project;

class UniqueProjectEntry implements Rule
{
    public function passes($attribute, $value)
    {
        $project = Project::where('uuid',request()->project_id)->first();
        if(!$project) return false;
        $exists = DB::table('task_metas')
            ->where('project_id', $project->id)
            ->where('type', request()->type)
            ->where('key', request()->key)
            ->whereJsonContains('value', request()->value) // For JSON
            ->exists();

        return !$exists;
    }

    public function message()
    {
        return 'This field is already exists with the same details.';
    }
}
