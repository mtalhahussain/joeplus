<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Portfolio, Project};

class PortfolioController extends Controller
{
    public function index(Request $request) {

        $portfolios = Portfolio::with(['projects','user:id,name,avatar'])->withCount('projects')->where('user_id',auth()->user()->id)->get();
        return $this->successResponse($portfolios, 'Portfolios fetched successfully');
    }

    public function store(Request $request) {

        $request->validate([
            'name' => 'required|string|max:255|unique:portfolios,name,NULL,id,user_id,' . auth()->user()->id,
            'description' => 'nullable|string|max:500',
        ]);

        $inputs = $request->all();
        $inputs['user_id'] = auth()->user()->id;
        $portfolio = Portfolio::create($inputs);
        return $this->successResponse($portfolio, 'Portfolio created successfully');
    }

    public function show($id) {

        $portfolio = Portfolio::with('user:id,name,email,avatar','projects')->where('uuid', $id)->first();
        if(!$portfolio) return $this->errorResponse([],'Portfolio not found', 422);

        $data = $portfolio->projects->map(function($project){
            return $project;
        });

        $response = [
            'portfolio_name' => $portfolio->name,
            'projects' => $data
        ];  
        
        return $this->successResponse($response, 'Portfolio fetched successfully');
    }

    public function update(Request $request, $id) {

        $portfolio = Portfolio::where('uuid', $id)->first();
        if(!$portfolio) return $this->errorResponse([],'Portfolio not found', 422);
        
        $portfolio->update($request->all());
        return $this->successResponse($portfolio, 'Portfolio updated successfully');
    }

    public function destroy($id) {
        $portfolio = Portfolio::where('uuid', $id)->first();
        if(!$portfolio){
            return $this->errorResponse([],'Portfolio not found', 422);
        }
        $portfolio->delete();
        $portfolio->projects()->detach();
        return $this->successResponse([], 'Portfolio deleted successfully');
    }

    public function assignProject(Request $request) {

        $request->validate([
            'portfolio_id' => 'required|exists:portfolios,uuid',
            'project_id' => 'required|exists:projects,uuid',
        ]);

        $portfolio = Portfolio::where('uuid', $request->portfolio_id)->first();
        $project = Project::where('uuid', $request->project_id)->first();
        if(!$project) return $this->errorResponse([], 'Portfolio not found', 422);
        $portfolio->projects()->attach($project->id);
        return $this->successResponse([], 'Project assigned to portfolio successfully');
    }
   
    public function assignRemove(Request $request) {

        $request->validate([
            'portfolio_id' => 'required|exists:portfolios,uuid',
            'project_id' => 'required|exists:projects,uuid',
        ]);

        $portfolio = Portfolio::where('uuid', $request->portfolio_id)->first();
        $project = Project::where('uuid', $request->project_id)->first();
        if(!$project) return $this->errorResponse([], 'Portfolio not found', 422);
        $portfolio->projects()->detach($project->id);
        return $this->successResponse([], 'Project removed from portfolio successfully');
    }
}
