<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Http\ViewModels;

use Drewlabs\Core\Validator\Traits\HasAuthenticatable;
use Drewlabs\Core\Validator\Traits\HasFileInputs;
use Drewlabs\Core\Validator\Traits\HasInputs;
use Drewlabs\Contracts\Validator\Validatable;

class PersonViewModel implements Validatable
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
	}

	/**
	 * Returns a fluent validation rules
	 * 
	 *
	 * @return array<string,string|string[]>
	 */
	public function messages()
	{
		# code...
		return [
			"firstname" => "required|max:50",
			"lastname" => "required|max:50",
		];
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
			"firstname" => "required|max:50",
			"lastname" => "required|max:50",
		];
	}

}