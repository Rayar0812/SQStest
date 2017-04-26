Laravel Queue with AWS SQS
===

## A. Before using AWS SQS

![](http://i.imgur.com/Zjy3IBz.png)

### 1. require AWS-sdk
```
"aws/aws-sdk-php": "~3.0"
```

### 2. Create a Queue on AWS SQS

Follow the instructions on AWS SQS to create your queue, and you will get infomation below:
```
Name:	myFirstSQS
URL:	https://sqs.us-west2.amazonaws.com/107581978295/myFirstSQS
ARN:	arn:aws:sqs:us-west-2:107581978295:myFirstSQS
```
![](https://i.imgur.com/hANBUMR.png)


### 3. get your AWS access key & sercet Key on AWS IAM
Follow the instructions on AWS IAM to create your application key,

and you will get infomation below:
```
AWS_SQS_KEY=AKIAIYR27EFU2OXTFPOA
AWS_SQS_SECRET=88IC9I93l6vZefDEUWB*******RFd3O1eWhxLJt
```
![](https://i.imgur.com/fakVYqC.png)

### 4. Config your laravel project

.env

Add AWS_SQS_EKY and AWS_SQS_SERCET on the bottom
```
AWS_SQS_KEY=your AWS access key
AWS_SQS_SECRET=your AWS sercet
```
\config\queue.php

Edit your 'sqs' detail like below:
```
 'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_SQS_KEY', ''),
            'secret' => env('AWS_SQS_SECRET', ''),
            'prefix' => 'https://sqs.us-west-2.amazonaws.com/107581978295/',
            'queue' => 'myFirstSQS',
            'region' => 'us-west-2',
        ],
```

## B. Using Laravel Queue

~~Read AWS SDK for PHP repice~~, Laravel is your best friend!

### 1. Create a Job
```
php artisan make:job doSomethingGoodJob
```

\app\Jobs\doSomethingGoodJob.php

```PHP
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;


class DoSomethingGoodJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }

}
```
Now, we want to do something...
```PHP
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\SQStest;

class DoSomethingGoodJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public $tries = 5;
    public $timeout = 120;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        SQStest::create($this->data);
    }

}
```

### 2. Create Controller to dispathing Jobs
```
php artisan make:controller firstSQSController
```
\app\Http\Controller\firstSQSController.php
```PHP
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class firstSQSController extends Controller
{
    //
}
```
Now, dispatching jobs to Queue
```PHP
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\SQStest;
use App\Jobs\DoSomethingGoodJob;
use Dusterio\PlainSqs\Jobs\DispatcherJob;

class firstSQSController extends Controller
{
    //
    public function index()
    {
        // Create a PHP object or do something
        $object = [
            'name' => 'test',
            'status' => 1
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
```

### 3. add it to Route

```PHP
Route::get('sqstest', 'firstSQSController@index')->name('SQS.test1');
```

### 4. How does it work?

1. Enable laravel Queue Worker
```
php artisan queue:work --daemon


php artisan queue:work
   {connection? : The name of connection}
   {--queue= : The queue to listen on}
   {--daemon : Run the worker in daemon mode (Deprecated)}
   {--once : Only process the next job on the queue}
   {--delay=0 : Amount of time to delay failed jobs}
   {--force : Force the worker to run even in maintenance mode}
   {--memory=128 : The memory limit in megabytes}
   {--sleep=3 : Number of seconds to sleep when no job is available}
   {--timeout=60 : The number of seconds a child process can run}
   {--tries=0 : Number of times to attempt a job before logging it failed}
```
2. Run Command/Url/etc. to dispatch your jobs to AWS SQS
3. When laravel Queue get new Object in Queue, it will run Job.handle()
4. When Job is finished,remove the job from AWS SQS. 

### 5. Install Supervisor

Supervisor is a process monitor for the Linux operating system, and will automatically restart your queue:work process if it fails. 

#### Install Supervisor:
```
sudo apt-get install supervisor
```

#### Configuring Supervisor:

CD to Config path
```
/etc/supervisor/conf.d
```
create a config file
```
file name: laravel-worker.conf

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/forge/app.com/artisan queue:work sqs --sleep=3 --tries=3
autostart=true
autorestart=true
user=forge
numprocs=8
redirect_stderr=true
stdout_logfile=/home/forge/app.com/worker.log

```

#### Staring Spuervisor

```
sudo supervisorctl reread

sudo supervisorctl update

sudo supervisorctl start laravel-worker:*

```

[Supervisor documentation](http://supervisord.org/index.html)


### 6. Dealing with fail jobs

```
php artisan queue:failed-table

php artisan migrate
```

When running your queue worker, you should specify the maximum number of times a job should be attempted using the ```--tries``` switch on the queue:work command.

```
php artisan queue:work sqs --tries=3
```

#### Cleaning Up After Failed Jobs

When Job failed to prosess, you need to send user notification of failure, etc...
Add ```public function failed(Exception $exception)``` into your Job.

```PHP
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\SQStest;

class DoSomethingGoodJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    public $tries = 5;
    public $timeout = 120;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        SQStest::create($this->data);
    }
    
    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // Send user notification of failure, etc...
    }

}
```

#### Retrying Failed Jobs

To view all failed job in database
```
php artisan queue:failed
```

Retry Jobs
```
Retry specific ID : php artisan queue:retry 5

Retry all : php artisan queue:retry all

```

Delete Failed Jobs
```
Delete specific ID : php artisan queue:forget 5

Delete all : php artisan queue:flush

```


### 7. Job event

Typically, you should call these methods from a service provider.

There're three event in Laravel Queue : before, after, failing and looping.

For example, we may use the AppServiceProvider.


AppServiceProvider

```PHP
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
         Queue::before(function (JobProcessing $event) {
            // $event->connectionName
            // $event->job
            // $event->job->payload()
        });

        Queue::after(function (JobProcessed $event) {
            // $event->connectionName
            // $event->job
            // $event->job->payload()
        });

        Queue::failing(function (JobFailed $event) {
            // $event->connectionName
            // $event->job
            // $event->exception
        });
        
        // execute before the worker attempts to fetch a job from a queue.
        Queue::looping(function () {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

```
