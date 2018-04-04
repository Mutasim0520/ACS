<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Journal extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

   public function user(){
       return $this->belongsTo('App\User');
   }

   public function purchase(){
       return $this->belongsTo('App\Purchase');
   }

   public function sale()
   {
       return $this->belongsTo('App\Sale');
   }

   public function ledger(){
       return $this->belongsToMany('App\Ledger')->withPivot('account_type','value');
   }

}
