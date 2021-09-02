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

use Drewlabs\Packages\Database\Traits\Model;
use Drewlabs\Contracts\Data\Model\ActiveModel;
use Drewlabs\Contracts\Data\Model\Parseable;
use Drewlabs\Contracts\Data\Model\Relatable;
use Drewlabs\Contracts\Data\Model\GuardedModel;
use Illuminate\Database\Eloquent\Model as EloquentModel;

final class ClientVerifiedPhoneNumber extends EloquentModel implements ActiveModel, Parseable, Relatable, GuardedModel
{

	use Model;

	/**
	 * Model referenced table
	 * 
	 * @var string
	 */
	protected $table = "client_verified_phone_numbers";

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
		"id",
		"phone_number",
		"session_client_id",
		"verification_code",
		"expires_at",
		"expired",
		"verified_at",
		"created_at",
		"updated_at",
	];

	/**
	 * List of fillable properties of the current model
	 * 
	 * @var array
	 */
	public $relation_methods = [];

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