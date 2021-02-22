<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $fillable = [
        'gameid', 'cardnum','ownernum','level'
    ];
    public $timestamps = false;
}
