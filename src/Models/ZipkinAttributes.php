<?php

namespace Mts88\LaravelZipkin\Models;

use Illuminate\Http\Request;

class ZipkinAttributes
{

    private $user      = null;
    private $routeName = null;
    private $ip        = '127.0.0.1';
    private $rootSpan  = null;

    public function __construct()
    {

    }

    public function setRootSpan($span)
    {
        $this->rootSpan = $span;
        return $this;
    }

    public function getRootSpan()
    {
        return $this->rootSpan;
    }

    public function setRequest(Request $request)
    {
        $this->routeName = $request->route()->getName();
        $this->ip        = $request->ip();
        return $this;
    }

    public function getRouteName()
    {
        return $this->routeName;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getUsername()
    {
        return is_null($this->user) ? null : $this->user->username;
    }
}
