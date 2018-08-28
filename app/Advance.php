<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Advance extends Model
{

    public function purchase(){
        return $this->belongsTo('App\Purchase');
    }

    public function sale(){
        return $this->belongsTo('App\Sale');
    }
}
