<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Purchase extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    public function supplier(){
        return $this->belongsTo('App\Supplier');
    }

    public function product(){
        return $this->hasMany('App\Product');
    }

    public function user(){
        return $this->belongsTo('App\User');
    }

    public function purchase_historie(){
        return $this->hasMany('App\Purchase_historie');
    }

    public function journal(){
        return $this->hasOne('App\Journal');
    }

    public function accounts_purchase_historie(){
        return $this->hasMany('App\Accounts_purchase_historie');
    }

}
