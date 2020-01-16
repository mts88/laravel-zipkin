<?php

namespace Mts88\LaravelZipkin\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Mts88\LaravelZipkin\Services\ZipkinService;

class ZipkinRequestLogger
{
    private $zipkinService;

    public function __construct(ZipkinService $zipkinService)
    {
        $this->zipkinService = $zipkinService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (in_array($request->method(), $this->zipkinService->getAllowedMethods())) {

            $this->zipkinService->setTracer($request->route()->getName(), $request->ip());

            foreach ($request->query() as $key => $value) {
                $tags["query." . $key] = $value;
            }

            $this->zipkinService->createRootSpan('incoming_request', ($tags ?? []))
                ->setRootSpanMethod($request->method())
                ->setRootSpanPath($request->path())
                ->setRootAuthUser(Auth::user())
                ->setRootSpanTag('request.headers', json_encode($request->headers->all()))
                ->setRootSpanTag('request.body', json_encode($request->all()));
        }

        return $next($request);
    }

    public function terminate($request, $response)
    {

        if (!is_null($this->zipkinService->getRootSpan())) {
            $this->zipkinService->setRootSpanStatusCode($response->getStatusCode())->closeSpan();
        }

    }

}
