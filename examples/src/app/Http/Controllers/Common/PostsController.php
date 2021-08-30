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

use App\Services\PersonService;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Contracts\Validator\Validator;
use Drewlabs\Packages\Http\Contracts\IActionResponseHandler;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

final class PostsController
{

	/**
	 * Injected instance of MVC service
	 * 
	 * @var ActionHandler
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
	 * Class instance initializer
	 * 
	 * @param Validator $validator
	 * @param IActionResponseHandler $response
	 * @param ActionHandler $service
	 *
	 * @return self
	 */
	public function __construct(Validator $validator, IActionResponseHandler $response, ActionHandler $service)
	{
		# code...
		$this->validator = $validator;
		$this->response = $response;
		$this->service = $service ?? new PersonService();
	}

	/**
	 * Handles http request action
	 * 
	 * @param Request $request
	 *
	 * @return Response
	 */
	public function __invoke(Request $request)
	{
		# code...
	}

}