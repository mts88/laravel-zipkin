<?php

namespace Mts88\LaravelZipkin\Facades;

use \Illuminate\Support\Facades\Facade;

class Zipkin extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'Mts88\LaravelZipkin\Services\ZipkinService';
    }

}
