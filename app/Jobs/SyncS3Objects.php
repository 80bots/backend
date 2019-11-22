<?php

namespace App\Jobs;

use App\BotInstance;
use App\Helpers\InstanceHelper;
use App\Services\Aws;
use Aws\Exception\AwsException;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncS3Objects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array
     */
    private $credentials;

    /**
     * @var BotInstance
     */
    private $instance;

    /**
     * Create a new job instance.
     *
     * @param BotInstance $instance
     */
    public function __construct(BotInstance $instance)
    {
        $this->credentials = [
            'key'    => config('aws.iam.access_key'),
            'secret' => config('aws.iam.secret_key')
        ];

        $this->instance = $instance;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $now = Carbon::now()->toDateTimeString();

        $aws = new Aws;
        $aws->s3Connection('', $this->credentials);

        $next   = '';
        $folder = config('aws.streamer.folder');
        $prefix = "{$folder}/{$this->instance->tag_name}";

        Log::debug("Start SyncS3Objects {$prefix}: {$now}");

        do {

            try {

                $result = $aws->getS3ListObjects($aws->getS3Bucket(), 500, $prefix, $next);

                if ($result->hasKey('IsTruncated') && $result->get('IsTruncated')) {
                    if ($result->hasKey('NextContinuationToken')) {
                        $next = $result->get('NextContinuationToken');
                    }
                } else {
                    $next = '';
                }

                if ($result->hasKey('Contents')) {

                    $contents = collect($result->get('Contents'))->map(function ($item, $key) {
                        return $item['Key'];
                    });

                    if ($contents->isNotEmpty()) {
                        foreach ($contents as $path) {
                            $object = str_replace("{$prefix}/", '', $path);
                            InstanceHelper::getObjectByPath($this->instance->id, $object);
                        }
                    }
                }

            } catch (AwsException $exception) {
                Log::error($exception->getMessage());
            } catch (Throwable $throwable) {
                Log::error($throwable->getMessage());
            }

        } while (! empty($next));

        $completed = Carbon::now()->toDateTimeString();

        Log::debug("Completed SyncS3Objects {$prefix}: {$completed}");
    }
}
