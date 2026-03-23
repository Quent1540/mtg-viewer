<?php

namespace App\Eventlistener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiLogListener {
    public function __construct(private readonly LoggerInterface $logger){
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void{
        $request = $event->getRequest();
        if (str_starts_with($request->getPathInfo(), '/api')) {
            $this->logger->info(sprintf('API Request: %s %s', $request->getMethod(), $request->getPathInfo()));
        }
    }
}