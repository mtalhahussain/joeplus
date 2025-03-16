<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Favorite, Portfolio, Project};

class FavoriteController extends Controller
{
    public function index(Request $request) {

        $projects = auth()->user()->favoriteProjects()->get();
        $portfolios = auth()->user()->favoritePortfolios()->get();

        $merged = $projects->merge($portfolios);
        return $this->successResponse($merged, 'Favorites fetched successfully');
    }

    public function store(Request $request) {

        $request->validate([
            'portfolio_id' => 'nullable|exists:portfolios,uuid',
            'project_id' => 'nullable|exists:projects,uuid',
        ]);

        $project = $portfolio = null;

        if($request->has('project_id')) $project = Project::where('uuid', $request->project_id)->first();
        
        if($request->has('portfolio_id')) $portfolio = Portfolio::where('uuid', $request->portfolio_id)->first();
        $message = 'Favorite added successfully';
        
        if($project) {
            $result = $project->favorites()->toggle(auth()->user()->id);
            $message = empty($result['attached']) ? 'Favorite removed successfully' : 'Favorite added successfully';
        }
        
        if($portfolio) {
            $result = $portfolio->favorites()->toggle(auth()->user()->id);
            $message = empty($result['attached']) ? 'Favorite removed successfully' : 'Favorite added successfully';
        }

        return $this->successResponse([], $message);
    }

    public function destroy($id) {
        $favorite = Favorite::where('id', $id)->first();
        if(!$favorite){
            return $this->errorResponse([],'Favorite not found', 422);
        }
        $favorite->delete();
        return $this->successResponse([], 'Favorite deleted successfully');
    }
}
