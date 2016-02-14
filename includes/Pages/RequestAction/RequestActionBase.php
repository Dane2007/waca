<?php

namespace Waca\Pages\RequestAction;

use PdoDatabase;
use Request;
use Waca\Exceptions\ApplicationLogicException;
use Waca\PageBase;
use Waca\WebRequest;

abstract class RequestActionBase extends PageBase
{
	/**
	 * @param PdoDatabase $database
	 *
	 * @return Request
	 * @throws ApplicationLogicException
	 */
	protected function getRequest(PdoDatabase $database)
	{
		$requestId = WebRequest::postInt('request');
		if ($requestId === null) {
			throw new ApplicationLogicException('Request ID not found');
		}

		/** @var Request $request */
		$request = Request::getById($requestId, $database);

		if ($request === false) {
			throw new ApplicationLogicException('Request not found');
		}

		return $request;
	}

	final protected function checkPosted()
	{
		// if the request was not posted, send the user away.
		if (!WebRequest::wasPosted()) {
			throw new ApplicationLogicException('This page does not support GET methods.');
		}
	}
}