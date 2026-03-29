<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use App\Catalog\Domain\Exception\ProductNotFoundException;
use App\Review\Domain\Exception\ReviewAlreadyExistsException;
use App\User\Domain\Exception\EmailAlreadyExistsException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious() ?? $exception;
        }

        [$status, $message] = match (true) {
            $exception instanceof ReviewAlreadyExistsException => [Response::HTTP_CONFLICT, $exception->getMessage()],
            $exception instanceof EmailAlreadyExistsException  => [Response::HTTP_CONFLICT, $exception->getMessage()],
            $exception instanceof ProductNotFoundException      => [Response::HTTP_NOT_FOUND, $exception->getMessage()],
            $exception instanceof \InvalidArgumentException     => [Response::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage()],
            $exception instanceof HttpExceptionInterface        => [$exception->getStatusCode(), $exception->getMessage()],
            default => [null, null],
        };

        if ($status === null) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => $message], $status));
    }
}
