@extends('layouts.app')

@section('title')
    Bots View
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">View Bot</h5>
                <a href="{{route('admin.bots.index')}}" class="btn btn-primary btn-round">Back</a>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Bot Name*</label>
                            <input type="text" name="bot_name" value="{{isset($bots->bot_name) ? $bots->bot_name : ''}}"
                                   class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">AMI Image ID*</label>
                            <input type="text" name="aws_ami_image_id"
                                   value="{{isset($bots->aws_ami_image_id) ? $bots->aws_ami_image_id : ''}}"
                                   class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">AMI Name*</label>
                            <input type="text" name="aws_ami_name"
                                   value="{{isset($bots->aws_ami_name) ? $bots->aws_ami_name : ''}}"
                                   class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Instance Type*</label>
                            <input type="text" name="aws_instance_type"
                                   value="{{isset($bots->aws_instance_type) ? $bots->aws_instance_type : ''}}"
                                   class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="aws_startup_script">Startup Script</label>
                            <textarea name="aws_startup_script" class="form-control"
                                      readonly>{{isset($bots->aws_startup_script)?$bots->aws_startup_script:''}}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Storage GB*</label>
                            <input type="text" name="aws_storage_gb"
                                   value="{{isset($bots->aws_storage_gb) ? $bots->aws_storage_gb : ''}}"
                                   class="form-control" readonly/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')

@endsection
