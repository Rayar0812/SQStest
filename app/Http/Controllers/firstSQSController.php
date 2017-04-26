<?php

namespace App\Http\Controllers;

use App\SQStest;
use App\Jobs\DoSomethingGoodJob;
use Illuminate\Http\Request;
use Dusterio\PlainSqs\Jobs\DispatcherJob;

class firstSQSController extends Controller
{
    //
    public function index()
    {
        // Create a PHP object or do something
        $object = [
            'name' => 'test',
            'status' => 0
        ];

        //new a Job object
        $job = (new DoSomethingGoodJob($object))
                        ->onConnection('sqs');
        //              ->onQueue('myFirstSQS');
        //              ->delay(Corbon::now()->addminutes(10));
        //              *** AWS SQS max delay time is 15 minutes. ***
        //if you want to upload Image or Video, need to encode to base64 first.

        //push this job to Queue (AWS SQS)
        dispatch($job);
    }
}
