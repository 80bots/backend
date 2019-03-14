<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\AppController;
use App\UserInstances;
use Aws\Ec2\Ec2Client;
use Illuminate\Http\Request;

class UserInstancesController extends AppController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $ec2Client = new Ec2Client([
            'region' => 'us-east-2',
            'version' => 'latest',
            'credentials' => [
                'key'    => 'AKIAIO7MFUMEZ33ZDXKA',
                'secret' => '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',
            ],
        ]);

        $keyPairName = time().'_darshan';
        $result = $ec2Client->createKeyPair(array(
            'KeyName' => $keyPairName
        ));
        // Save the private key
//        $saveKeyLocation = getenv('HOME') . "/.ssh/{$keyPairName}.pem";
        $saveKeyLocation = public_path(). "/uploads/ssh_keys/".time()."_{$keyPairName}.pem";
//        file_put_contents($saveKeyLocation, $result['keyMaterial']);
            // Update the key's permissions so it can be used with SSH
        chmod($saveKeyLocation, 0600);

        /*$result = $ec2Client->runInstances(array(
            'DryRun' => false,
            'ImageId' => 'ami-0cd3dfa4e37921605',
            'MinCount' => 1,
            'MaxCount' => 1,
        ));*/


//        $result = $ec2Client->describeInstances();
        /*$instanceIds = array('InstanceID1', 'InstanceID2');
        $monitorInstance = 'ON';
        if ($monitorInstance == 'ON') {
            $result = $ec2Client->monitorInstances(array(
                'InstanceIds' => $instanceIds
            ));
        } else {
            $result = $ec2Client->unmonitorInstances(array(
                'InstanceIds' => $instanceIds
            ));
        }*/
        dd($result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function show(UserInstances $userInstances)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function edit(UserInstances $userInstances)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, UserInstances $userInstances)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\UserInstances  $userInstances
     * @return \Illuminate\Http\Response
     */
    public function destroy(UserInstances $userInstances)
    {
        //
    }
}
