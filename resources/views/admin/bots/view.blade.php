@extends('layouts.app')

@section('title')
    {{ __('admin.bots.view_title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('admin.bots.view_bot') }}</h5>
                <a href="{{route('admin.bots.index')}}" class="btn btn-primary btn-round">{{ __('keywords.back') }}</a>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.bot_name') }}*</label>
                            <input type="text" name="bot_name" value="{{isset($bots->bot_name) ? $bots->bot_name : ''}}" class="form-control" readonly />
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.ami_id') }}*</label>
                            <input type="text" name="aws_ami_image_id" value="{{isset($bots->aws_ami_image_id) ? $bots->aws_ami_image_id : ''}}" class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.ami_name') }}*</label>
                            <input type="text" name="aws_ami_name" value="{{isset($bots->aws_ami_name) ? $bots->aws_ami_name : ''}}" class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.instance_type') }}*</label>
                            <input type="text" name="aws_instance_type" value="{{isset($bots->aws_instance_type) ? $bots->aws_instance_type : ''}}" class="form-control" readonly/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.startup_script') }}*</label>
                            <textarea name="aws_startup_script" class="form-control" readonly>{{isset($bots->aws_startup_script)?$bots->aws_startup_script:''}}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.storage') }}*</label>
                            <input type="text" name="aws_storage_gb" value="{{isset($bots->aws_storage_gb) ? $bots->aws_storage_gb : ''}}" class="form-control" readonly/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')

@endsection
