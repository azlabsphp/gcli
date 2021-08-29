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

use Drewlabs\Support\Immutable\ValueObject;

class PersonDto extends ValueObject
{

	/**
	 * @var array
	 */
	private $___hidden = [];

	/**
	 * @var array
	 */
	private $___guarded = [];

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
			"firstname" => "firstname",
			"lastname" => "lastname",
			"phoneNumber" => "phone_number",
		];
	}

}