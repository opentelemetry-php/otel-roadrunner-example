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

$tracerProvider = (new TracerProviderFactory())->create();
$tracer = $tracerProvider->getTracer('example');

$worker = new RoadRunner\Http\PSR7Worker($worker, $psrFactory, $psrFactory, $psrFactory);

while ($req = $worker->waitRequest()) {
    try {
        $parent = TraceContextPropagator::getInstance()->extract($req->getHeaders());
        $rootSpan = $tracer
            ->spanBuilder('root')
            ->setParent($parent)
            ->startSpan();
        $scope = $rootSpan->activate();
        try {
            $childSpan = $tracer
                ->spanBuilder('child')
                ->startSpan();
            $rsp = new Psr7\Response();
            $rsp->getBody()->write('Hello world!');

            $worker->respond($rsp);
            $childSpan->end();
            $rootSpan->end();
        } finally {
            //detach scope, clearing state for next request
            $scope->detach();
        }
    } catch (\Throwable $e) {
        $worker->getWorker()->error((string)$e);
    }
}
