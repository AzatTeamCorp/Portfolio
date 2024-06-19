<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ShowController;
use Symfony\Component\HttpFoundation\Response;

class CheckPlayerJoined
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $show_id = $request->input('show_id');
        $player_uid = Auth::guard('show')->user()->player_uid;
        $playerInShow = Show::findOrFail($show_id)->players()->where('player_uid', $player_uid)->exists();
        if (!$playerInShow) {
            return app(ShowController::class)->unauthenticated($request);
        }

        return $next($request);
    }
}
