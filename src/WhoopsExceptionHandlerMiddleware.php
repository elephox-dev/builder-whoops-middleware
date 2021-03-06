<?php
declare(strict_types=1);

namespace Elephox\Builder\Whoops;

use Closure;
use Elephox\Http\Contract\ResponseBuilder;
use Elephox\Mimey\MimeType;
use Elephox\Stream\StringStream;
use Elephox\Web\Middleware\DefaultExceptionHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\XmlResponseHandler;
use Whoops\RunInterface as WhoopsRunInterface;

class WhoopsExceptionHandlerMiddleware extends DefaultExceptionHandler
{
	/**
	 * @param Closure(): WhoopsRunInterface $whoopsRunInterfaceFactory
	 */
	public function __construct(
		private readonly Closure $whoopsRunInterfaceFactory,
	) {
	}

	protected function setResponseBody(ResponseBuilder $response): ResponseBuilder
	{
		$runner = ($this->whoopsRunInterfaceFactory)();
		$exception = $response->getException();
		if ($exception === null) {
			if ($response->getBody() === null) {
				return $response->body(new StringStream('No exception to handle found'));
			}

			return $response;
		}

		if (empty($runner->getHandlers())) {
			if ($contentType = $response->getContentType()) {
				$runner->pushHandler(match ($contentType->getValue()) {
					MimeType::ApplicationJson->getValue() => new JsonResponseHandler(),
					MimeType::ApplicationXml->getValue() => new XmlResponseHandler(),
					MimeType::TextPlain->getValue() => new PlainTextHandler(),
					default => new PrettyPageHandler(),
				});
			} else {
				$runner->pushHandler(new PrettyPageHandler());
				$response->contentType(MimeType::TextHtml);
			}
		}

		$runner->allowQuit(false);
		$runner->writeToOutput(false);

		/** @var non-empty-string $content */
		$content = $runner->handleException($exception);

		return $response->body(new StringStream($content));
	}
}
