<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class checkAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
         
         if(!Auth::check()){
            session()->flash('errors', 'Invalid Credentials');
            return redirect('/')->withErrors([
               'credentials' => 'Invalid credentials, please login first !!!'
            ]);
         } else {
            $user = Auth::user();
            if($user->role === 'superadmin')
            {
               session()->flash('role', 'superadmin');
               return $next($request);
            } 
         }
         session()->flash('role', 'admin');
         return $next($request);
    }
}