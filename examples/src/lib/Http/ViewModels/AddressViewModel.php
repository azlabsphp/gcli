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

class AddressViewModel implements Validatable
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
			"email" => ['nullable','string','max:190'],
			"postal_box" => ['nullable','string','max:255'],
			"house_no" => ['nullable','string','max:65535'],
			"country" => ['required_without:id','string','max:255'],
			"city" => ['required_without:id','string','max:255'],
			"area" => ['nullable','string','max:255'],
			"district" => ['nullable','string','max:255'],
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
			"email" => ['nullable','string','max:190'],
			"postal_box" => ['nullable','string','max:255'],
			"house_no" => ['nullable','string','max:65535'],
			"country" => ['sometimes','string','max:255'],
			"city" => ['sometimes','string','max:255'],
			"area" => ['nullable','string','max:255'],
			"district" => ['nullable','string','max:255'],
			"created_at" => ['nullable','date_format:Y-m-d H:i:s'],
			"updated_at" => ['nullable','date_format:Y-m-d H:i:s'],
		];
	}

}