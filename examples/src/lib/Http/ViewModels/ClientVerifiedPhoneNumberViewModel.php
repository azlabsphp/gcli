<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Http\Controllers\ViewModels;

use Drewlabs\Core\Validator\Traits\HasAuthenticatable;
use Drewlabs\Core\Validator\Traits\HasFileInputs;
use Drewlabs\Core\Validator\Traits\HasInputs;
use Drewlabs\Contracts\Validator\Validatable;

class ClientVerifiedPhoneNumberViewModel implements Validatable
{

	use HasAuthenticatable;
	use HasFileInputs;
	use HasInputs;

	/**
	 * Returns a fluent validation rules
	 * 
	 *
	 * @return array<string,string|string[]>
	 */
	public function rules()
	{
		# code...
		return [
			"id" => ['sometimes','integer'],
			"phone_number" => ['required_without:id','string','max:20'],
			"session_client_id" => ['required_without:id','string','max:100'],
			"verification_code" => ['required_without:id','string','max:10'],
			"expires_at" => ['required_without:id','date_format:Y-m-d H:i:s'],
			"expired" => ['required_without:id','boolean'],
			"verified_at" => ['nullable','date_format:Y-m-d H:i:s'],
			"created_at" => ['nullable','date_format:Y-m-d H:i:s'],
			"updated_at" => ['nullable','date_format:Y-m-d H:i:s'],
		];
	}

	/**
	 * Returns a list of validation error messages
	 * 
	 *
	 * @return array<string,string|string[]>
	 */
	public function messages()
	{
		# code...
		return [];
	}

	/**
	 * Returns a fluent validation rules applied during update actions
	 * 
	 *
	 * @return array<string,string|string[]>
	 */
	public function updateRules()
	{
		# code...
		return [
			"id" => ['sometimes','integer'],
			"phone_number" => ['sometimes','string','max:20'],
			"session_client_id" => ['sometimes','string','max:100'],
			"verification_code" => ['sometimes','string','max:10'],
			"expires_at" => ['sometimes','date_format:Y-m-d H:i:s'],
			"expired" => ['sometimes','boolean'],
			"verified_at" => ['nullable','date_format:Y-m-d H:i:s'],
			"created_at" => ['nullable','date_format:Y-m-d H:i:s'],
			"updated_at" => ['nullable','date_format:Y-m-d H:i:s'],
		];
	}

}