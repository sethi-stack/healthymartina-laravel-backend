<?php
namespace App\Permissions;

use App\Permission;
use App\Role;

trait HasPermissionsTrait {

  public function role() {
    return $this->belongsTo(Role::class,'role_id');
  }

  public function hasRole($role ) {
      if ($this->role->slug == $role) {
        return true;
      }
    return false;
  }
  public function permissions() {
    return $this->role->permissions;
  }

  public function hasPermission($permission) {
        foreach ($this->role->permissions as $key => $value) {
            if ($value->slug == $permission) {
            return true;
            }         
        }
        return false;
  }

  protected function getAllPermissions(array $permissions) {

    return Permission::whereIn('slug',$permissions)->get();
    
  }

}