<?php

namespace App\Console\Commands;

use App\AwsAmi;
use App\AwsRegion;
use App\Services\Aws;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AwsSyncAmis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:sync-amis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @throws Exception
     */
    public function handle()
    {
        $owners     = config('aws.owners', ['030500410996']);
        $regions    = AwsRegion::get();

        if ($regions->isNotEmpty()) {
            foreach ($regions as $region) {

                Log::debug("Sync Ami in {$region->code} region");

                $aws = new Aws;
                if (! empty($owners) && is_array($owners)) {
                    foreach ($owners as $owner) {
                        $result = $aws->describeImages($region->code ?? null, $owner);
                        if ($result->hasKey('Images')) {
                            $images = $result->get('Images');
                            $this->saveImages($region->id ?? null, $images);
                        }
                    }
                }
                unset($aws, $result, $images);
            }
        }
    }

    /**
     * @param int $regionId
     * @param array $images
     * @throws Exception
     */
    private function saveImages(int $regionId, array $images): void
    {
        $images = collect($images);

        if ($images->isNotEmpty()) {

            foreach ($images as $image) {
                $data = [
                    'aws_region_id' => $regionId,
                    'name' => $image['Name'] ?? '',
                    'description' => $image['Description'] ?? '',
                    'architecture' => $image['Architecture'] ?? '',
                    'source' => $image['ImageLocation'] ?? '',
                    'image_type' => $image['ImageType'] ?? '',
                    'owner' => $image['OwnerId'] ?? '',
                    'visibility' => ($image['Public']) ? AwsAmi::VISIBILITY_PUBLIC : AwsAmi::VISIBILITY_PRIVATE,
                    'status' => $image['State'] ?? '',
                    'ena_support' => $image['EnaSupport'] ?? false,
                    'hypervisor' => $image['Hypervisor'] ?? '',
                    'root_device_name' => $image['RootDeviceName'] ?? '',
                    'root_device_type' => $image['RootDeviceType'] ?? '',
                    'sriov_net_support' => $image['SriovNetSupport'] ?? '',
                    'virtualization_type' => $image['VirtualizationType'] ?? '',
                    'creation_date' => Carbon::parse($image['CreationDate'])->toDateTimeString(),
                ];
                AwsAmi::updateOrInsert(
                    $data,
                    [ 'image_id' => $image['ImageId'] ?? null ]
                );
            }
        }
    }
}
