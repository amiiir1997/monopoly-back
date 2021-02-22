<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
     protected $fillable = [
        'gameid', 'suggestnum','dealnum','suggestcards','dealcards','suggestmoney','dealmoney','time'
    ];
    public $timestamps = false;
}
