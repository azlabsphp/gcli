<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Http\Controllers\Controllers;

use App\Services\ClientVerifiedPhoneNumberService;
use App\Models\ClientVerifiedPhoneNumber;
use App\Http\Controllers\ViewModels\ClientVerifiedPhoneNumberViewModel;
use App\DataTransfertObject\ClientVerifiedPhoneNumberDto;
use Drewlabs\Core\Validator\Exceptions\ValidationException;
use Drewlabs\Contracts\Validator\Validator;
use Drewlabs\Packages\Http\Contracts\IActionResponseHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Function import statements
use function Drewlabs\Support\Proxy\Action;

final class ClientVerifiedPhoneNumbersController
{

	/**
	 * Injected instance of MVC service
	 * 
	 * @var ClientVerifiedPhoneNumberService
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
	 * Resource primary key name
	 * 
	 * @var string
	 */
	private const RESOURCE_PRIMARY_KEY = "id";

	/**
	 * Class instance initializer
	 * 
	 * @param Validator $validator
	 * @param IActionResponseHandler $response
	 * @param ClientVerifiedPhoneNumberService $service
	 *
	 * @return self
	 */
	public function __construct(Validator $validator, IActionResponseHandler $response, ClientVerifiedPhoneNumberService $service = null)
	{
		# code...
		$this->validator = $validator;
		$this->response = $response;
		$this->service = $service;
	}

	/**
	 * Display or Returns a list of items
	 * @Route /GET /clientverifiedphonenumbers[/{$id}]
	 * 
	 * @param Request $request
	 * @param string $id
	 *
	 * @return JsonResponse
	 */
	public function index(Request $request, string $id = null)
	{
		# code...
		if (!is_null($id)) {
			return $this->show($request, $id);
		}
		// TODO : Provides policy handlers
		$tranformFunc_ = function( $items) {
			return map_query_result($items, function ($value) {
				return $value ? (new ClientVerifiedPhoneNumberDto)->withModel($value) : $value;
			});
		};
		$filters = drewlabs_databse_parse_client_request_query(new ClientVerifiedPhoneNumber, $request);;
		$result = $this->service->handle(Action([
			'type' => 'SELECT',
			'payload' => $request->has('per_page') ? [$filters, (int)$request->get('per_page'), $request->has('page') ? (int)$request->get('page') : null] : [$filters],
		]), $tranformFunc_);
		return $this->response->ok($result);
	}

	/**
	 * Display or Returns an item matching the specified id
	 * @Route /GET /clientverifiedphonenumbers/{$id}
	 * 
	 * @param Request $request
	 * @param mixed $id
	 *
	 * @return JsonResponse
	 */
	public function show(Request $request, $id)
	{
		# code...
		// TODO: Provide Policy handlers if required
		$result = $this->service->handle(Action([
			'type' => 'SELECT',
			'payload' => [$id],
		]), function($value) {
			return null !== $value ? new ClientVerifiedPhoneNumberDto($value->toArray()) : $value;
		});
		return $this->response->ok($result);
	}

	/**
	 * Stores a new item in the storage
	 * @Route /POST /clientverifiedphonenumbers
	 * 
	 * @param Request $request
	 *
	 * @return JsonResponse
	 */
	public function store(Request $request)
	{
		# code...
		try {
			// validate request inputs
			$viewModel_ = (new ClientVerifiedPhoneNumberViewModel)->setUser($request->user())->set($request->all())->files($request->allFiles());
		
			$result = $this->validator->validate($viewModel_, $request->all(), function() use ($viewModel_) {
				return $this->service->handle(Action([
					'type' => 'CREATE',
					'payload' => [
						$viewModel_->all(),
						$viewModel_->has(self::RESOURCE_PRIMARY_KEY) ?
							[
								'upsert' => true,
								'upsert_conditions' => [
									self::RESOURCE_PRIMARY_KEY => $viewModel_->get(self::RESOURCE_PRIMARY_KEY),
								],
							] :
							[]
					],
				]), function( $value) {
					return null !== $value ? new ClientVerifiedPhoneNumberDto($value->toArray()) : $value;
				});
			});
		
			return $this->response->ok($result);
		} catch (ValidationException $e) {
			// Return failure response to request client
			return $this->response->badRequest($e->getErrors());
		} catch (\Exception $e) {
			// Return failure response to request client
			return $this->response->error($e);
		}
	}

	/**
	 * Update the specified resource in storage.
	 * @Route /PUT /clientverifiedphonenumbers/{id}
	 * @Route /PATCH /clientverifiedphonenumbers/{id}
	 * 
	 * @param Request $request
	 * @param mixed $id
	 *
	 * @return JsonResponse
	 */
	public function update(Request $request, $id)
	{
		# code...
		try {
			$request = $request->merge(["id" => $id]);
			// validate request inputs
			// Use your custom validation rules here
			$viewModel_ = (new ClientVerifiedPhoneNumberViewModel)->setUser($request->user())->set($request->all())->files($request->allFiles());
		
			$result = $this->validator->setUpdate(true)->validate($viewModel_, $request->all(), function() use ($id, $viewModel_) {
				return $this->service->handle(Action([
					'type' => 'UPDATE',
					'payload' => [$id, $viewModel_->all()],
				]), function( $value) {
					return null !== $value ? new ClientVerifiedPhoneNumberDto($value->toArray()) : $value;
				});
			});
		
			return $this->response->ok($result);
		} catch (ValidationException $e) {
			// Return failure response to request client
			return $this->response->badRequest($e->getErrors());
		} catch (\Exception $e) {
			// Return failure response to request client
			return $this->response->error($e);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 * @Route /DELETE /clientverifiedphonenumbers/{id}
	 * 
	 * @param Request $request
	 * @param mixed $id
	 *
	 * @return JsonResponse
	 */
	public function destroy(Request $request, $id)
	{
		# code...
		// TODO: Provide Policy handlers if required
		$result = $this->service->handle(Action([
			'type' => 'DELETE',
			'payload' => [$id],
		]));
		return $this->response->ok($result);
	}

}