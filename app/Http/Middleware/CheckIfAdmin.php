<?php

namespace App\Http\Middleware;

use Closure;

class CheckIfAdmin
{
    /**
     * Checked that the logged in user is an administrator.
     *
     * --------------
     * VERY IMPORTANT
     * --------------
     * If you have both regular users and admins inside the same table,
     * change the contents of this method to check that the logged in user
     * is an admin, and not a regular user.
     *
     * @param [type] $user [description]
     *
     * @return bool [description]
     */
    private function checkIfUserIsAdmin($user)
    {
        // Check if user has is_admin field (most common Backpack setup)
        if (property_exists($user, 'is_admin') || isset($user->is_admin)) {
            return $user->is_admin == 1 || $user->is_admin === true;
        }
        
        // Alternative: Check role_id if you use roles (role_id = 1 is typically admin)
        if (property_exists($user, 'role_id') || isset($user->role_id)) {
            // Adjust role_id == 1 to match your admin role ID
            return $user->role_id == 1;
        }
        
        // If neither exists, deny access for security
        return false;
    }

    /**
     * Answer to unauthorized access request.
     *
     * @param [type] $request [description]
     *
     * @return [type] [description]
     */
    private function respondToUnauthorizedRequest($request)
    {  
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        } else {
            backpack_auth()->logout();
            return redirect()->guest(backpack_url('login'));
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Temporarily disable admin check for debugging
        return $next($request);
        
        if (backpack_auth()->guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        if (!$this->checkIfUserIsAdmin(backpack_user())) {
            return $this->respondToUnauthorizedRequest($request);
        }

        return $next($request);
    }
}
