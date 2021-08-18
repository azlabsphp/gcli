<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Http\Controllers\Common;

use App\Services\PersonsService;
use Drewlabs\Contracts\Validator\Validator;
use Drewlabs\Packages\Http\Contracts\IActionResponseHandler;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

final class PostsController
{

	/**
	 * Injected instance of MVC service
	 * 
	 * @var PersonsService
	 */
	private $service;

	/**
	 * Injected instance of the validator class
	 * 
	 * @var Validator
	 */
	private $validator;

	/**
	 * Injected instance of the response handler class
	 * 
	 * @var IActionResponseHandler
	 */
	private $response;

	/**
	 * Create a new Http Controller class
	 * 
	 * @param Validator validator
	 * @param IActionResponseHandler response
	 * @param PersonsService service
	 *
	 * @return self
	 */
	public function __construct(Validator $validator, IActionResponseHandler $response, PersonsService $service)
	{
		# code...
		$this->validator = $validator;
		$this->response = $response;
	}

	/**
	 * Handles http request action
	 * 
	 * @param Request request
	 *
	 * @return Response
	 */
	public function __invoke(Request $request)
	{
		# code...
	}

}