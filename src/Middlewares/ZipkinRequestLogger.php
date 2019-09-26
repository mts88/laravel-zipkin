<?php

namespace Mts88\LaravelZipkin\Middlewares;

use Closure;
use Illuminate\Support\Facades\Route;
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
        $route = Route::getRoutes()->match($request);

        $this->zipkinService->setTracer($route->getName(), $request->ip());

        foreach ($request->query() as $key => $value) {
            $tags["query." . $key] = $value;
        }

        $this->zipkinService->setRootSpan('incoming_request', ($tags ?? []))
            ->setRootSpanMethod($request->method())
            ->setRootSpanPath($request->path());

        return $next($request);
    }

    public function terminate($request, $response)
    {

        $this->zipkinService->setRootSpanStatusCode($response->getStatusCode())
            ->getRootSpan()
            ->finish();

        $this->zipkinService->getTracer()->flush();

    }

}
