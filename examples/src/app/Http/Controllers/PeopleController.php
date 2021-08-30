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

use App\Services\PersonService;
use App\Models\Person;
use App\DataTransfertObject\PersonDto;
use Drewlabs\Core\Validator\Exceptions\ValidationException;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Contracts\Validator\Validator;
use Drewlabs\Packages\Http\Contracts\IActionResponseHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Function import statements
use function Drewlabs\Support\Proxy\Action;

final class PeopleController
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
	 * @param ActionHandler $service
	 *
	 * @return self
	 */
	public function __construct(Validator $validator, IActionResponseHandler $response, ActionHandler $service = null)
	{
		# code...
		$this->validator = $validator;
		$this->response = $response;
		$this->service = $service ?? new PersonService();
	}

	/**
	 * Display or Returns a list of items
	 * @Route /GET /people[/{$id}]
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
		$tranformFunc_ = function ($items) {
			return map_query_result($items, function ($value) {
				return $value ? (new PersonDto)->withModel($value) : $value;
			});
		};
		$filters = drewlabs_databse_parse_client_request_query(new Person, $request);;
		$result = $this->service->handle(
			Action([
				'type' => 'SELECT',
				'payload_' => $request->has('per_page') ? [$filters, (int)$request->get('per_page'), $request->has('page') ? (int)$request->get('page') : null] : [$filters],
			]),
			$tranformFunc_
		);
		return $this->response->ok($result);
	}

	/**
	 * Display or Returns an item matching the specified id
	 * @Route /GET /people/{$id}
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
			'payload_' => [$id],
		]), function ($value) {
			return null !== $value ? new PersonDto($value->toArray()) : $value;
		});
		return $this->response->ok($result);
	}

	/**
	 * Stores a new item in the storage
	 * @Route /POST /people
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
			$result = $this->validator->validate([], $request->all(), function () use ($request) {
				// After validation logic goes here...
				return $this->service->handle(Action([
					'type' => 'CREATE',
					'payload_' => [
						$request->all(),
						$request->has(self::RESOURCE_PRIMARY_KEY) ?
							[
								'upsert' => true,
								'upsert_conditions' => [
									self::RESOURCE_PRIMARY_KEY => $request->get(self::RESOURCE_PRIMARY_KEY),
								],
							] :
							[]
					],
				]), function ($value) {
					return null !== $value ? new PersonDto($value->toArray()) : $value;
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
	 * @Route /PUT /people/{id}
	 * @Route /PATCH /people/{id}
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
			$result = $this->validator->setUpdate(true)->validate([], $request->all(), function () use ($id, $request) {
				// After validation logic goes here...
				return $this->service->handle(Action([
					'type' => 'UPDATE',
					'payload_' => [$id, $request->all()],
				]), function ($value) {
					return null !== $value ? new PersonDto($value->toArray()) : $value;
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
	 * @Route /DELETE /people/{id}
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
			'payload_' => [$id],
		]));
		return $this->response->ok($result);
	}
}
