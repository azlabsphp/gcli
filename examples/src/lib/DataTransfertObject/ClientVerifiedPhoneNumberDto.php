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

class ClientVerifiedPhoneNumberDto extends ModelValueObject
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
			"phoneNumber" => "phone_number",
			"sessionClientId" => "session_client_id",
			"verificationCode" => "verification_code",
			"expiresAt" => "expires_at",
			"expired" => "expired",
			"verifiedAt" => "verified_at",
			"createdAt" => "created_at",
			"updatedAt" => "updated_at",
		];
	}

}