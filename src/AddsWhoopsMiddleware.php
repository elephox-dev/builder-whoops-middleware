<?php
declare(strict_types=1);

namespace Elephox\Builder\Whoops;

use Elephox\DI\Contract\ServiceCollection;
use Elephox\Support\Contract\ExceptionHandler;
use Elephox\Web\RequestPipelineBuilder;
use Whoops\Run as WhoopsRun;
use Whoops\RunInterface as WhoopsRunInterface;

trait AddsWhoopsMiddleware
{
	abstract protected function getServices(): ServiceCollection;

	abstract protected function getPipeline(): RequestPipelineBuilder;

	public function addWhoops(bool $registerAsExceptionHandler = true): void
	{
		$whoopsExceptionHandler = new WhoopsExceptionHandlerMiddleware(fn () => $this->getServices()->requireService(WhoopsRunInterface::class));

		$this->getPipeline()->push($whoopsExceptionHandler);
		$this->getServices()->addSingleton(WhoopsRunInterface::class, WhoopsRun::class);

		if ($registerAsExceptionHandler) {
			$this->getServices()->addSingleton(ExceptionHandler::class, instance: $whoopsExceptionHandler, replace: true);
		}
	}
}
