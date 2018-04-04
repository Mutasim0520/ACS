<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Sub_categorie extends Model
{
    protected $fillable = [
        'name'
    ];

    public function category(){
        return $this->belongsToMany('App\Categorie');
    }

    public function product(){
        return $this->hasMany('App\Product');
    }

}
