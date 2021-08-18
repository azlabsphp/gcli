<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Http\Controllers;

use Drewlabs\Contracts\Validator\Validator;
use Drewlabs\Packages\Http\Contracts\IActionResponseHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PeopleController
{

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
	 *
	 * @return self
	 */
	public function __construct(Validator $validator, IActionResponseHandler $response)
	{
		# code...
		$this->validator = $validator;
		$this->response = $response;
	}

	/**
	 * Display or Returns a list of items
	 * @Route /GET /people[/{$id}]
	 * 
	 * @param Request request
	 * @param string id
	 *
	 * @return JsonResponse
	 */
	public function index(Request $request, string $id = null)
	{
		# code...
		if (!is_null($id)) {
			return $this->show($request, $id);
		}
		
			// Code goes here...
		
	}

	/**
	 * Display or Returns an item matching the specified id
	 * @Route /GET /people/{$id}
	 * 
	 * @param Request request
	 * @param mixed id
	 *
	 * @return JsonResponse
	 */
	public function show(Request $request,  $id)
	{
		# code...
			// Code goes here...
		
	}

	/**
	 * Stores a new item in the storage
	 * @Route /POST /people
	 * 
	 * @param Request request
	 *
	 * @return JsonResponse
	 */
	public function store(Request $request)
	{
		# code...
		try {
			// validate request inputs
			// Use your custom validation rules here
			$validator = $this->validator->validate([], $request->all());
			if ($validator->fails()) {
				return $this->response->badRequest($validator->errors());
			}
		
			// Code goes here...
		
		} catch (\Exception $e) {
			// Return failure response to request client
			return $this->response->error($e);
		}
	}

	/**
	 * Update the specified resource in storage.
	 * @Route /PUT /people/{id}
	 * @Route /PATCH /people/{id}
	 * 
	 * @param Request request
	 * @param mixed id
	 *
	 * @return JsonResponse
	 */
	public function update(Request $request,  $id)
	{
		# code...
		try {
			$request = $request->merge(["id" => $id]);
			// validate request inputs
			// Use your custom validation rules here
			$validator = $this->validator->setUpdate(true)->validate([], $request->all());
			if ($validator->fails()) {
				return $this->response->badRequest($validator->errors());
			}
		
			// Code goes here...
		
		} catch (\Exception $e) {
			// Return failure response to request client
			return $this->response->error($e);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 * @Route /DELETE /people/{id}
	 * 
	 * @param Request request
	 * @param mixed id
	 *
	 * @return JsonResponse
	 */
	public function destroy(Request $request,  $id)
	{
		# code...
		try {
			// Code goes here ...
		} catch (\Exception $e) {
			// Return failure response to request client
			return $this->response->error($e);
		}
	}

}