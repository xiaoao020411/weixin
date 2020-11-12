<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WxModel extends Model
{
    protected $table = 'wxuser';
    public $timestamps = false;
    protected $guarded = [];   //黑名单  create只需要开启
}
