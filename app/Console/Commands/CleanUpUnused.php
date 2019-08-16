<?php

namespace App\Console\Commands;

use App\Http\Resources\User\UserInstanceCollection;
use App\Http\Resources\User\UserInstanceResource;
use App\Services\Aws;
use App\UserInstance;
use App\UserInstancesDetails;
use Illuminate\Console\Command;

class CleanUpUnused extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'instance:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up unused security groups';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * TODO: Get all the removed instances for the last day and remove connected groups,
         * as it is impossible to remove a group once the instance isn't fully removed on AWS
         */

//        $aws = new Aws;
//
//        $aws->deleteSecurityGroup('sg-06cf38cb1f88fa060');
//
//        $aws->deleteS3KeyPair('keys/0Mn1rfPmIEKQQ3QQHJmy_psbt.pem');
//
//        UserInstance::onlyTrashed()->chunk(100, function ($instances) use ($aws) {
//            foreach ($instances as $instance) {
//
//                $aws->deleteSecurityGroup($instance->aws_security_group_id);
//
//                if(preg_match('/^keys\/(.*)\.pem$/s', $instance->aws_pem_file_path, $matches)) {
//                    $aws->deleteKeyPair($matches[1]);
//                    $aws->deleteS3KeyPair($instance->aws_pem_file_path);
//                }
//                $aws->deleteSecurityGroup($instance->aws_security_group_id);
//            }
//        });
    }
}
