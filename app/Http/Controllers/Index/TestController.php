<?php

namespace App\Http\Controllers\Index;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class TestController extends Controller
{
    public function huizi(){
        return view('test.huizi');
    }
}
