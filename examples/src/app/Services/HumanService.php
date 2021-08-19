<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Services;

use Drewlabs\Packages\Database\EloquentDMLManager;
use Drewlabs\Contracts\Support\Actions\ActionPayload;
use Drewlabs\Contracts\Support\Actions\Exceptions\InvalidActionException;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Support\Actions\ActionResult as ActionsActionResult;
use Drewlabs\Contracts\Support\Actions\Action;
use Closure;

// Function import statements
use function Drewlabs\Core\Support\Proxy\ActionResult;

final class HumanService implements ActionHandler
{

	/**
	 * Database query manager
	 * 
	 * @var DMLProvider
	 */
	private $dbManager;

	/**
	 * Creates an instance of the Service
	 * 
	 * @param DMLProvider manager
	 *
	 * @return self
	 */
	public function __construct(DMLProvider $manager = null)
	{
		# code...
		$this->dbManager = $manager ?? new EloquentDMLManager(Test::class);
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @param Action action
	 * @param Closure callback
	 *
	 * @return ActionsActionResult
	 */
	public function handle(Action $action, Closure $callback = null)
	{
		# code...
		$payload = $action->payload();
		$payload = $payload instanceof ActionPayload ? $payload->toArray() : (is_array($payload) ? $payload : []);
		
		// Handle switch statements
		switch (strtoupper($action->type())) {
			case "CREATE":
				//Create handler code goes here
				$payload = null !== $callback ? array_merge($payload, [$callback]) : $payload;
				return ActionResult($this->dbManager->create(...$payload));
			case "UPDATE":
				//Update handler code goes here
				$payload = null !== $callback ? array_merge($payload, [$callback]) : $payload;
				return ActionResult($this->dbManager->update(...$payload));
			case "DELETE":
				//Delete handler code goes here
				return ActionResult($this->dbManager->delete(...$payload));
			case "SELECT":
				//Select handler code goes here
				$payload = null !== $callback ? array_merge($payload, [$callback]) : $payload;
				return ActionResult($this->dbManager->select(...$payload));
			default:
				//Provides default handler or throws exception
				throw new InvalidActionException("This " . __CLASS__ . " can only handle CREATE,DELETE,UPDATE AND SELECT actions");
		}
	}

}