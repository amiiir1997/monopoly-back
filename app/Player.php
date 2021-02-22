<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'playercode', 'gameid','money','passleft','place','gamenumber','name','jail'
    ];

    public $timestamps = false;
}
