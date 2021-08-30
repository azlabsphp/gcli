<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\DataTransfertObject;

use Drewlabs\Support\Immutable\ModelValueObject;

class TestDto extends ModelValueObject
{

	/**
	 * @var array
	 */
	private $___hidden = [
		"password",
	];

	/**
	 * @var array
	 */
	private $___guarded = [
		"id",
	];

	/**
	 * Returns the list of JSON serializable properties
	 * 
	 *
	 * @return array
	 */
	protected function getJsonableAttributes()
	{
		# code...
		return [
			"username",
			"password",
			"email",
			"id",
		];
	}

}