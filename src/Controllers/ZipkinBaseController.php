<?php

namespace Mts88\LaravelZipkin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Mts88\LaravelZipkin\Services\ZipkinService;
use Zipkin\Timestamp;

class ZipkinBaseController extends Controller
{
    private $span;
    public $zipkinService;

    public function __construct(ZipkinService $zipkinService)
    {
        $this->zipkinService = $zipkinService;

    }

    public function callAction($method, $parameters)
    {
        if (is_null($this->zipkinService->getRootSpan())) {
            return parent::callAction($method, $parameters);
        }

        $classCaller = get_called_class();
        $className   = Arr::last(explode("\\", $classCaller));

        $tracing = $this->zipkinService->createTracing($className, Request::getClientip());
        $tracer  = $tracing->getTracer();

        $this->span = $tracer->nextSpan($this->zipkinService->getRootSpanContext());
        $this->span->annotate("Start", Timestamp\now());
        $this->span->setName($method);
        $this->span->start(Timestamp\now());
        $this->span->tag("class", $classCaller);
        $this->span->tag("method", $method);
        $this->span->tag("user", Auth::user()->username ?? 'anonymous');

        $action = parent::callAction($method, $parameters);

        $this->span->annotate("End", Timestamp\now());
        $this->span->finish(Timestamp\now());
        $tracer->flush();

        return $action;
    }

    public function __destruct()
    {

    }

}
