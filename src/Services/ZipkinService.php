<?php

namespace Mts88\LaravelZipkin\Services;

use const Zipkin\Kind\SERVER;
use Zipkin\Span;
use Zipkin\Tracer;
use \Zipkin\Endpoint;
use \Zipkin\Propagation\DefaultSamplingFlags;
use \Zipkin\Samplers\BinarySampler;
use \Zipkin\Timestamp;
use \Zipkin\TracingBuilder;

class ZipkinService
{

    const REPORTER_URL               = "/api/v2/spans";
    const ROOT_SPAN_HTTP_STATUS_CODE = "http.status_code";
    const ROOT_SPAN_HTTP_PATH        = "http.path";
    const ROOT_SPAN_HTTP_METHOD      = "http.method";

    private $config;
    private $httpReporterURL;
    private $tracing;
    private $tracer;
    private $rootSpan;

    public function __construct()
    {
        $this->config          = config('zipkin');
        $this->httpReporterURL = $this->config["host"] . ":" . $this->config["port"] . self::REPORTER_URL;

        return $this;
    }

    public function setTracer(string $tracer = "middleware", string $ipV4 = "127.0.0.1")
    {
        $this->tracing = $this->createTracing($tracer, $ipV4);

        $this->tracer = $this->tracing->getTracer();

        return $this;
    }

    public function getTracer(): ?Tracer
    {
        return $this->tracer;
    }

    public function setRootSpan(string $name, array $tags)
    {
        /* Always sample traces */
        $defaultSamplingFlags = DefaultSamplingFlags::createAsSampled();

        /* Creates the main span */
        $this->rootSpan = $this->tracer->newTrace($defaultSamplingFlags);
        $this->rootSpan->start(Timestamp\now());
        $this->rootSpan->setName($name);
        $this->rootSpan->setKind(SERVER);

        foreach ($tags as $key => $value) {
            $this->rootSpan->tag($key, $value);
        }

        return $this;
    }

    public function getRootSpan(): ?Span
    {
        return $this->rootSpan;
    }

    public function setRootSpanPath(string $path)
    {
        $this->rootSpan->tag(self::ROOT_SPAN_HTTP_PATH, $path);

        return $this;
    }

    public function setRootSpanMethod(string $method)
    {
        $this->rootSpan->tag(self::ROOT_SPAN_HTTP_METHOD, $method);
        return $this;
    }

    public function setRootSpanStatusCode(string $code)
    {
        $this->rootSpan->tag(self::ROOT_SPAN_HTTP_STATUS_CODE, $code);
        return $this;
    }

    public function setRootSpanAnnotation($key, $value)
    {
        $this->rootSpan->annotate($key, $value);
        return $this;
    }

    public function getRootSpanContext()
    {
        return $this->rootSpan->getContext();
    }

    public function createChild(string $name, bool $autostart = false): ?Span
    {
        $span = $this->tracer->newChild($this->rootSpan->getContext());
        $span->setName($name);
        if ($autostart) {
            $span->start(Timestamp\now());
        }

        return $span;
    }

    public function createTracing($localServiceName, $localServiceIPv4, $localServicePort = null)
    {
        $endpoint = Endpoint::create($localServiceName, $localServiceIPv4, null, $localServicePort);

        $reporter = new \Zipkin\Reporters\Http(
            \Zipkin\Reporters\Http\CurlFactory::create(),
            ['endpoint_url' => $this->httpReporterURL]
        );

        $sampler = BinarySampler::createAsAlwaysSample();
        $tracing = TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();

        return $tracing;

    }
}
