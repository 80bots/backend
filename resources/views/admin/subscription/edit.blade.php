@extends('layouts.app')

@section('title')
    {{ __('admin.bots.edit_title') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <form class="card" id="plan-edit" action="{{route('admin.subscription.update',$id)}}" method="post">
            @method('PUT')
            @csrf
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('admin.subscription.update_plan') }}</h5>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="row">
                    <div class="offset-3 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('admin.subscription.plan_name') }}*</label>
                            <input type="text" name="plan_name" value="{{isset($plan->name) ? $plan->name : ''}}" class="form-control">
                        </div>
                    </div>
                    <div class="offset-3 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('admin.subscription.price') }}*</label>
                            <input type="text" name="price" value="{{isset($plan->price) ? $plan->price : ''}}" class="form-control"/>
                        </div>
                    </div>
                    <div class="offset-3 col-md-6 col-sm-12">
                        <div class="form-group">
                            <label for="">{{ __('admin.subscription.credit') }}*</label>
                            <input type="text" name="credit" value="{{isset($plan->credit) ? $plan->credit : ''}}" class="form-control"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary btn-round">{{ __('keywords.save') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script>
        $("#plan-edit").validate({
            rules: {
                plan_name: {
                    required: true
                },
                price: {
                    required: true
                },
                credit: {
                    required: true
                },
            }
        });
    </script>
@endsection
