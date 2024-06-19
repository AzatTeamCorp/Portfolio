<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JoinUrlExists
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $presentation = \App\Models\Presentation::where('join_url', $request->route('code'))->first();
        if (!$presentation) {
            abort(404);
        }
        return $next($request);
    }
}
