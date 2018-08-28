<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Sale extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public function buyer(){
        return $this->belongsTo('App\Buyer');
    }

    public function product(){
        return $this->belongsToMany('App\Product')->withPivot('price','total_amount');
    }

    public function journal(){
        return $this->hasOne('App\Journal');
    }

    public function sales_historie(){
        return $this->hasMany('App\Sales_historie');
    }
    public function accounts_sale_historie(){
        return $this->hasMany('App\Accounts_sale_historie');
    }

    public function advance(){
        return $this->hasOne('App\Advance');
    }


}
