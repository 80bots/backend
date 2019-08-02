@extends('layouts.app')

@section('title')
    {{ __('admin.bots.create_title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <form class="card" id="bot-create" action="{{route('admin.bots.store')}}" method="post">
            @csrf
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('admin.bots.add_bot') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('admin.bots.platform') }}*</label>
                            <input type="text" name="platform" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.bot_name') }}*</label>
                            <input type="text" name="bot_name" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.ami_id') }}*</label>
                            <input type="text" name="aws_ami_image_id" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.ami_name') }}</label>
                            <input type="text" name="aws_ami_name" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.instance_type') }}*</label>
                            <input type="text" name="aws_instance_type" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.storage') }}*</label>
                            <input type="text" name="aws_storage_gb" class="form-control"/>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label for="aws_startup_script">{{ __('keywords.bots.startup_script') }}</label>
                            <textarea name="aws_startup_script" class="form-control" spellcheck="false" rows="23"></textarea>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12">
                        <div class="form-group">
                            <label for="aws_custom_script">{{ __('keywords.bots.bot_script') }}</label>
                            <textarea id="aws_custom_script" name="aws_custom_script" spellcheck="false" class="form-control"
                                      rows="23"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.bot_desc') }}*</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('keywords.bots.tags') }}</label>
                            <textarea name="tags" class="form-control"
                                      placeholder="{{ __('keywords.bots.tags_placeholder') }}"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-round">{{ __('keywords.add') }}</button>
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
