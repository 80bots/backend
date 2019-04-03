@extends('layouts.app')

@section('title')
    Bots Edit
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <form class="card" id="bot-create" action="{{route('admin.bots.update',$id)}}" method="post">
            @method('PATCH')
            @csrf
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Update Bot</h5>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Platform*</label>
                            <select name="Platform" class="form-control">
                                <option value="">Select Platform</option>
                                @if(!$platforms->isEmpty())
                                    @foreach($platforms as $platform)
                                        <option value="{{$platform->id}}" @if($bots->platform_id == $platform->id) selected @endif>{{ !empty($platform->name) ? $platform->name : '' }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Bot Name*</label>
                            <input type="text" name="bot_name" value="{{isset($bots->bot_name) ? $bots->bot_name : ''}}" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">AMI Image Id*</label>
                            <input type="text" name="aws_ami_image_id" value="{{isset($bots->aws_ami_image_id) ? $bots->aws_ami_image_id : ''}}" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">AMI Name*</label>
                            <input type="text" name="aws_ami_name" value="{{isset($bots->aws_ami_name) ? $bots->aws_ami_name : ''}}" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Instance Type*</label>
                            <input type="text" name="aws_instance_type" value="{{isset($bots->aws_instance_type) ? $bots->aws_instance_type : ''}}" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Storage GB*</label>
                            <input type="text" name="aws_storage_gb" value="{{isset($bots->aws_storage_gb) ? $bots->aws_storage_gb : ''}}" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">StartUp Script*</label>
                            <textarea name="aws_startup_script" class="form-control">{{isset($bots->aws_startup_script)?$bots->aws_startup_script:''}}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Bot Description*</label>
                            <textarea name="description" class="form-control">{{isset($bots->description)?$bots->description:''}}</textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Tags*</label>
                            <textarea name="tags" class="form-control" placeholder="Comma(,) consider as new Tags">{{isset($tags)?$tags:''}}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-round">Save</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script>
        $("#bot-create").validate({
            rules: {
                bot_name: {
                    required: true
                },
                aws_ami_image_id: {
                    required: true
                },
                aws_ami_name: {
                    required: true
                },
                aws_instance_type: {
                    required: true
                },
                aws_startup_script: {
                    required: true
                },
                aws_storage_gb: {
                    required: true
                }
            }
        });
    </script>
@endsection