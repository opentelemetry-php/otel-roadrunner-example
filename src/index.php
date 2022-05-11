<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Common\Log\LoggerHolder;
use Psr\Log\LogLevel;
use Spiral\RoadRunner;
use Nyholm\Psr7;
use OpenTelemetry\SDK\Trace\TracerProviderFactory;

include "vendor/autoload.php";

$worker = RoadRunner\Worker::create();
$psrFactory = new Psr7\Factory\Psr17Factory();

LoggerHolder::set(
    new Logger('otel-php', [new StreamHandler(STDERR, LogLevel::DEBUG)])
);

$tracerProvider = (new TracerProviderFactory('example'))->create();
$tracer = $tracerProvider->getTracer();

$worker = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

while ($req = $worker->waitRequest()) {
    try {
        $context = TraceContextPropagator::getInstance()->extract($req->getHeaders());
        $span = $tracer->spanBuilder('root')->setParent($context)->startSpan();
        $rsp = new Psr7\Response();
        $rsp->getBody()->write('Hello world!');

        $worker->respond($rsp);
        $span->end();
    } catch (\Throwable $e) {
        $worker->getWorker()->error((string)$e);
    }
}
