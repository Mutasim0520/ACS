<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Ledger_categorie extends Model
{
    protected $fillable = ['name'];

    public function ledger(){
        return $this->belongsToMany('App\Ledger');
    }

}
