<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePlayer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {   
        $code = $request->route('code');
        if (Auth::guard('show')->check()) {
            $presentation = \App\Models\Presentation::where('join_url', $code)->first();
            $showId = Auth::guard('show')->user()->show_id;
            $firstShowId = $presentation->shows[0]->id;
            if ($firstShowId == $showId) {
                return $next($request);
            }
        }
        else {
            return redirect()->route('show.player', ['code' => $code]);
        }
        
    }
}
