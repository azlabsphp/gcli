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

class ClientFingerprintDto extends ModelValueObject
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
			"id" => "id",
			"clientId" => "client_id",
			"finger" => "finger",
			"fingerprintImageId" => "fingerprint_image_id",
			"createdAt" => "created_at",
			"updatedAt" => "updated_at",
		];
	}

}