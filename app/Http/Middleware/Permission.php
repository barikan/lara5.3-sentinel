<?php

namespace App\Http\Middleware;
use Closure;
use Sentinel;
use Redirect;
class Permission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string $permission チェックするパーミッション
     * @return mixed
     */
    public function handle($request, Closure $next, $permission)
    {
        if (($user=Sentinel::check()) && $user->hasAccessRoles($permission)) {
            return $next($request);
        }
        return Redirect::back()->withInput()->withErrors(['permission' => trans('sentinel.permission_denied')]);
    }
}