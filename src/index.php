<?php

include "vendor/autoload.php";

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\SDK\Common\Log\LoggerHolder;
use Psr\Log\LogLevel;
use Spiral\RoadRunner;
use Nyholm\Psr7;
use OpenTelemetry\SDK\Trace\TracerProviderFactory;

$worker = RoadRunner\Worker::create();
$psrFactory = new Psr7\Factory\Psr17Factory();
$logger = new Logger('otel-php', [new StreamHandler(STDERR, LogLevel::DEBUG)]);
LoggerHolder::set($logger);

$tracerProvider = (new TracerProviderFactory('example'))->create();
$tracer = $tracerProvider->getTracer();

$worker = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

while ($req = $worker->waitRequest()) {
    try {
        $context = TraceContextPropagator::getInstance()->extract($req->getHeaders());
        $rootSpan = $tracer->spanBuilder('root')->setParent($context)->startSpan();
        $scope = $rootSpan->activate();
        try {
            $childSpan = $tracer->spanBuilder('child')->startSpan();
            $rsp = new Psr7\Response();
            $rsp->getBody()->write('Hello world!');

            $worker->respond($rsp);
            $childSpan->end();
            $rootSpan->end();
        } finally {
            //detach scope, clearing state for next request
            if ($error = $scope->detach()) {
                $logger->error('Error detaching scope');
            }
        }
    } catch (\Throwable $e) {
        $worker->getWorker()->error((string)$e);
    }
}
