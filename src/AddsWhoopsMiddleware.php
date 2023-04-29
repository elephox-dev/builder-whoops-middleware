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
		$this->getServices()->tryAddSingleton(WhoopsRunInterface::class, WhoopsRun::class);

		$runner = $this->getServices()->require(WhoopsRunInterface::class);
		$handler = new WhoopsExceptionHandlerMiddleware($runner);

		// replace existing exception handler, if any
		$this->getPipeline()->exceptionHandler($handler);

		if ($registerAsExceptionHandler) {
			$this->getServices()->addSingleton(ExceptionHandler::class, instance: $handler);
		}
	}
}
