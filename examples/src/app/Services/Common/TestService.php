<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Services\Common;

use Drewlabs\Packages\Database\EloquentDMLManager;
use Drewlabs\Contracts\Support\Actions\ActionHandler;
use Drewlabs\Contracts\Data\DML\DMLProvider;
use Drewlabs\Contracts\Support\Actions\ActionResult;
use Drewlabs\Contracts\Support\Actions\Action;
use Closure;

final class TestService implements ActionHandler
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
	 * @param DMLProvider $manager
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
	 * @param Action $action
	 * @param Closure $callback
	 *
	 * @return ActionResult
	 */
	public function handle(Action $action, Closure $callback = null)
	{
		# code...
		// Handle switch statements
		switch (strtoupper($action->type())) {
			case "CREATE":
				//Create handler code goes here
				return;
			case "UPDATE":
				//Update handler code goes here
				return;
			case "DELETE":
				//Delete handler code goes here
				return;
			case "SELECT":
				//Select handler code goes here
				return;
			default:
				//Provides default handler or throws exception
				return;
		}
	}

}