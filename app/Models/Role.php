<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description'];
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
    
    public function hasPermission($permission)
    {
        return $this->permissions->contains('name', $permission);
    }
}