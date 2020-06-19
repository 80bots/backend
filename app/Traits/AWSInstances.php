<?php

namespace App\Traits;
use Aws\Ec2\Ec2Client;

trait AWSInstances
{
    /**
     * @return array
     */
    public function sync()
    {
        $ec2Client = new Ec2Client([
            'region' => config('aws.region'),
            'version' => config('aws.version'),
            'credentials' => config('aws.credentials'),
        ]);

        $result = $ec2Client->describeInstances();
        $reservations = $result->get('Reservations');

        $instancesByStatus = [];
        foreach ($reservations as $reservation) {
            $instances = $reservation['Instances'];
            if ($instances) {
                foreach ($instances as $instance) {
                    try {
                        $name  = null;
                        $email = null;
                        if( isset($instance['Tags']) && count($instance['Tags'])) {
                            foreach ($instance['Tags'] as $key => $tag) {
                                if(isset($tag['Key']) && $tag['Key'] == 'Name') {
                                    $name = $tag['Value'];
                                }
                                if(isset($tag['Key']) && $tag['Key'] == 'User Email') {
                                    $email = $tag['Value'];
                                }
                            }
                        }
                        if($name && $name == 'SaaS') {
                          continue;
                        }
                        $instancesByStatus[$instance['State']['Name']][] = [
                            'tag_name'                => $name,
                            'tag_user_email'          => $email,
                            'aws_instance_id'         => $instance['InstanceId'],
                            'aws_ami_id'              => $instance['ImageId'],
                            'aws_security_group_id'   => isset($instance['SecurityGroups']) && count($instance['SecurityGroups']) ? $instance['SecurityGroups'][0]['GroupId'] : null,
                            'aws_security_group_name' => isset($instance['SecurityGroups']) && count($instance['SecurityGroups']) ? $instance['SecurityGroups'][0]['GroupName'] : null,
                            'aws_public_ip'           => $instance['PublicIpAddress'] ?? null,
                            'aws_public_dns'          => $instance['PublicDnsName'] ?? null,
                            'created_at'              => date('Y-m-d H:i:s', strtotime($instance['LaunchTime']))
                        ];

                    } catch (\Exception $e) {
                        \Log::info($e->getMessage());
                        \Log::info('An error occurred while syncing '. $instance['InstanceId']);
                    }

                }
            }
        }
        return $instancesByStatus;
    }
}
