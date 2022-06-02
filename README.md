# Opentelemetry + roadrunner example
Example of [roadrunner](roadrunner.dev)'s opentelemetry plugin integrating with [opentelemetry-php](https://github.com/open-telemetry/opentelemetry-php).

RoadRunner's otel middleware emits some spans, and also injects distributed trace headers into the request.
The PHP application then extracts those trace headers, so that its own traces can be correctly parented to the RR trace.

# usage

- `make all`
- `curl localhost`
- browse to [zipkin](http://localhost:9411/zipkin) and search for traces
