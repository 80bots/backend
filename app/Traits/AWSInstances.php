<?php

namespace App\Traits;
use Aws\Ec2\Ec2Client;
use App\Bots;

trait AWSInstances
{
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
                        $instancesByStatus[$instance['State']['Name']][] = [
                            'aws_instance_id'         => $instance['InstanceId'],
                            'aws_ami_id'              => $instance['ImageId'],
                            'aws_security_group_id'   => $instance['SecurityGroups'][0]['GroupId'],
                            'aws_security_group_name' => $instance['SecurityGroups'][0]['GroupName'],
                            'aws_public_ip'           => $instance['PublicIpAddress'],
                            'aws_public_dns'          => $instance['PublicDnsName'],
                            'created_at'              => date('Y-m-d H:i:s', strtotime($instance['LaunchTime']))
                        ];
                    } catch (\Exception $e) {
                        \Log::info('An error occurred while syncing '. $instance['InstanceId']);
                    }

                }
            }
        }

        return $instancesByStatus;
    }
}
