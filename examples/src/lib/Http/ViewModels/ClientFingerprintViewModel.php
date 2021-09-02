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

class ClientFingerprintViewModel implements Validatable
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
			"client_id" => ['required_without:id','string','max:45'],
			"finger" => ['required_without:id','string'],
			"fingerprint_image_id" => ['required_without:id','integer'],
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
			"client_id" => ['sometimes','string','max:45'],
			"finger" => ['sometimes','string'],
			"fingerprint_image_id" => ['sometimes','integer'],
			"created_at" => ['nullable','date_format:Y-m-d H:i:s'],
			"updated_at" => ['nullable','date_format:Y-m-d H:i:s'],
		];
	}

}