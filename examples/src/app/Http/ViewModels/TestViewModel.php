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
use Drewlabs\Contracts\Validator\CoreValidatable;

class TestViewModel implements CoreValidatable
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
			"firstname" => "required|max:50",
			"lastname" => "required|max:50",
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

}