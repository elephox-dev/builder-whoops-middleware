<?php
declare(strict_types=1);

namespace Elephox\Builder\Whoops;

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
	 * @param WhoopsRunInterface $whoopsRunInterface
	 */
	public function __construct(
		private readonly WhoopsRunInterface $whoopsRunInterface,
	) {
	}

	protected function setResponseBody(ResponseBuilder $response): ResponseBuilder
	{
		$exception = $response->getException();
		if ($exception === null) {
			if ($response->getBody() === null) {
				return $response->body(new StringStream('No exception to handle found'));
			}

			return $response;
		}

		if (empty($this->whoopsRunInterface->getHandlers())) {
			if ($contentType = $response->getContentType()) {
				$this->whoopsRunInterface->pushHandler(match ($contentType->getValue()) {
					MimeType::ApplicationJson->getValue() => new JsonResponseHandler(),
					MimeType::ApplicationXml->getValue() => new XmlResponseHandler(),
					MimeType::TextPlain->getValue() => new PlainTextHandler(),
					default => new PrettyPageHandler(),
				});
			} else {
				$this->whoopsRunInterface->pushHandler(new PrettyPageHandler());
				$response->contentType(MimeType::TextHtml);
			}
		}

		$this->whoopsRunInterface->allowQuit(false);
		$this->whoopsRunInterface->writeToOutput(false);

		$content = $this->whoopsRunInterface->handleException($exception);

		return $response->body(new StringStream($content));
	}
}
