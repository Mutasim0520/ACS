<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Categorie extends Model
{
    protected $fillable = ['name'];

    public function sub_category(){
        return $this->belongsToMany('App\Sub_categorie');
    }

    public function product(){
        return $this->hasMany('App\Product');
    }

}
