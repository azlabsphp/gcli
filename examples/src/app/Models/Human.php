<?php

/*
 * This file is auto generated using the Drewlabs Code Generator package
 *
 * (c) Sidoine Azandrew <contact@liksoft.tg>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/

namespace App\Models;

use Drewlabs\Core\Validator\Traits\HasAuthenticatable;
use Drewlabs\Core\Validator\Traits\HasFileInputs;
use Drewlabs\Packages\Database\Traits\Model;
use Drewlabs\Contracts\Validator\Validatable;
use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;

final class Human extends EloquentModel implements Validatable, ActiveModel, Parseable, Relatable, GuardedModel
{

	use HasAuthenticatable;
	use HasFileInputs;
	use Model;

	/**
	 * Model referenced table
	 * 
	 * @var string
	 */
	protected $table = "humans";

	/**
	 * Table primary key
	 * 
	 * @var string
	 */
	protected $primaryKey = "id";

	/**
	 * List of values that must be hidden when generating the json output
	 * 
	 * @var array
	 */
	protected $hidden = [];

	/**
	 * List of attributes that will be appended to the json output of the model
	 * 
	 * @var array
	 */
	protected $appends = [];

	/**
	 * List of fillable properties of the current model
	 * 
	 * @var array
	 */
	protected $fillable = [
		"firstname",
		"lastname",
	];

	/**
	 * List of fillable properties of the current model
	 * 
	 * @var array
	 */
	public $relation_methods = [];

	/**
	 * Indicates whether the primary key of the model is incrementable
	 * 
	 * @var bool
	 */
	public $incrementing;

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
		return [];
	}

	/**
	 * Bootstrap the model and its traits.
	 * 
	 *
	 * @return void
	 */
	protected static function boot()
	{
		# code...
		parent::boot();
	}

}