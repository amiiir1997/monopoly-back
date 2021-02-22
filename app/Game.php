<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'gamecode', 'creater','startingmoney','turn','dice1','dice2','start_at','created_at','step','playerscount'
    ];

    public $timestamps =false;
}
