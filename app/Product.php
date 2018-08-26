<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;

class Product extends Model
{
    public function categorie(){
        return $this->belongsTo('App\Categorie');
    }

    public function sub_categorie(){
        return $this->belongsTo('App\Sub_categorie');
    }

    public function size(){
        return $this->belongsToMany('App\Size','color_product_size')->withPivot('quantity','defected');
    }

    public function color(){
        return $this->belongsToMany('App\Color','color_product_size')->withPivot('quantity','defected');
    }

    public function purchase(){
        return $this->belongsTo('App\Purchase');
    }

    public function sale(){
        return $this->belongsToMany('App\Sale')->withPivot('price','total_amount');
    }

    public function product_image(){
        return $this->hasMany('App\Product_image');
    }

}
