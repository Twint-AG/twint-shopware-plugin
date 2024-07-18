<?php

declare(strict_types=1);

namespace Twint\ExpressCheckout\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Throwable;
use Twint\ExpressCheckout\Model\ApiResponse;
use Twint\Sdk\Exception\ApiFailure;
use Twint\Sdk\InvocationRecorder\InvocationRecordingClient;
use Twint\Sdk\InvocationRecorder\Value\Invocation;

class ApiService
{
    public function __construct(
        private readonly EntityRepository $repository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param callable|null $buildLogCallback A callback function to build the log. It should accept two parameters.
     */
    public function call(
        InvocationRecordingClient $client,
        string $method,
        array $args,
        bool $save = true,
        callable $buildLogCallback = null
    ): ApiResponse {
        try {
            $returnValue = $client->{$method}(...$args);
        } catch (Throwable $e) {
            $this->logger->error('TWINT API error: ' . $e->getMessage());
        } finally {
            $invocations = $client->flushInvocations();

            $log = $this->log($returnValue ?? null, $method, $invocations, $save, $buildLogCallback);
        }

        return new ApiResponse($returnValue ?? null, $log);
    }

    /**
     * @param Invocation[] $invocation
     */
    protected function log(
        mixed $returnValue,
        string $method,
        array $invocation,
        bool $save = true,
        callable $callback = null
    ): array {
        $log = [];

        try {
            list($request, $response, $soapRequests, $soapResponses, $soapActions, $exception) = $this->parse(
                $invocation
            );

            $log = [
                'apiMethod' => $method,
                'soapAction' => $soapActions,
                'request' => $request,
                'response' => $response,
                'soapRequest' => $soapRequests,
                'soapResponse' => $soapResponses,
                'exception' => $exception,
            ];

            if (is_callable($callback)) {
                $log = $callback($log, $returnValue);
            }

            if (!$save) {
                return $log;
            }

            $event = $this->repository->create([$log], Context::createDefaultContext());

            // @phpstan-ignore-next-line: Always has record here
            return $event->getEvents()
                ?->first()
                ?->getPayloads()[0];
        } catch (Throwable $e) {
            $this->logger->error('Cannot log TWINT transaction');
        }

        return $log;
    }

    /**
     * @param Invocation[] $invocations
     */
    protected function parse(array $invocations): array
    {
        $request = json_encode($invocations[0]->arguments());
        $exception = $invocations[0]->exception() ?? ' ';
        if ($exception instanceof ApiFailure) {
            $exception = $exception->getMessage();
        }
        $response = json_encode($invocations[0]->returnValue());
        $soapMessages = $invocations[0]->messages();
        $soapRequests = [];
        $soapResponses = [];
        $soapActions = [];
        foreach ($soapMessages as $soapMessage) {
            $soapRequests[] = $soapMessage->request()->body();
            $soapResponses[] = $soapMessage->response()?->body();
            $soapActions[] = $soapMessage->request()->action();
        }

        return [$request, $response, $soapRequests, $soapResponses, $soapActions, $exception];
    }

    /**
     * Utility method to save log
     */
    public function saveLog(array $log): EntityWrittenContainerEvent
    {
        if (isset($log['id'])) {
            return $this->repository->update([$log], Context::createDefaultContext());
        }

        return $this->repository->create([$log], Context::createDefaultContext());
    }
}
