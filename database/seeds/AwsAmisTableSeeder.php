<?php

use App\AwsAmi;
use App\AwsRegion;
use App\Services\Aws;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AwsAmisTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $owners     = config('aws.owners', ['030500410996']);
        $regions    = AwsRegion::get();

        if ($regions->isNotEmpty()) {
            foreach ($regions as $region) {

                echo "Region {$region->name}\n";

                $aws = new Aws;
                if (! empty($owners) && is_array($owners)) {
                    foreach ($owners as $owner) {
                        $result = $aws->describeImages($region->code ?? null, $owner);
                        if ($result->hasKey('Images')) {
                            $images = $result->get('Images');
                            $this->saveImages($region->id ?? null, $images);
                        }
                        unset($result);
                        unset($images);
                    }
                }
                unset($aws);
            }
        }
    }

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
