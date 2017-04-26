<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SQStest extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sqs_test';

    protected $guarded = [];

}
