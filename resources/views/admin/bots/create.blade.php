@extends('layouts.app')

@section('title')
    Bots Create
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <form class="card" id="bot-create" action="{{route('admin.bots.store')}}" method="post">
            @csrf
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Add Bot</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Platform*</label>
                            <input type="text" name="Platform" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Bot Name*</label>
                            <input type="text" name="bot_name" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">AMI Image ID*</label>
                            <input type="text" name="aws_ami_image_id" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">AMI Name</label>
                            <input type="text" name="aws_ami_name" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Instance Type*</label>
                            <input type="text" name="aws_instance_type" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Storage GB*</label>
                            <input type="text" name="aws_storage_gb" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label for="aws_startup_script">Startup Script</label>
                            <textarea name="aws_startup_script" class="form-control" rows="23"></textarea>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label for="aws_custom_script">Bot Script</label>
                            <textarea id="aws_custom_script" name="aws_custom_script" class="form-control"
                                      rows="23"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Bot Description*</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">Tags</label>
                            <textarea name="tags" class="form-control"
                                      placeholder="Comma(,) consider as new Tags"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-round">Add</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script>
        $("#bot-create").validate({
            rules: {
                platform: {
                    required: true
                },
                description: {
                    required: true
                },
                bot_name: {
                    required: true
                },
                aws_ami_image_id: {
                    required: true
                },
                aws_ami_name: {
                    required: false
                },
                aws_instance_type: {
                    required: true
                },
                aws_startup_script: {
                    required: false
                },
                aws_custom_script: {
                    required: false
                },
                aws_storage_gb: {
                    required: true
                },
                tags: {
                    required: false
                }
            }
        });
    </script>
@endsection
