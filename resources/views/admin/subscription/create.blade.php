@extends('layouts.app')

@section('title')
    {{ __('admin.subscription.title') . ' ' . __('keywords.create') }}
@endsection

@section('css')

@endsection

@section('content')
<div class="wrapper">
    <form class="card" id="plan-create" action="{{route('admin.plan.store')}}" method="post">
        @csrf
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('admin.subscription.add_plan') }}</h5>
        </div>
        <div class="card-body">
            @include('layouts.imports.messages')
            <div class="row">
                <div class="offset-3 col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('admin.subscription.name') }}*</label>
                        <input type="text" name="plan_name" class="form-control">
                    </div>
                </div>
                <div class="offset-3 col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('admin.subscription.price') }}*</label>
                        <input type="text" name="price" class="form-control"/>
                    </div>
                </div>
                <div class="offset-3 col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('admin.subscription.credit') }}*</label>
                        <input type="text" name="credit" class="form-control"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary btn-round">{{ __('admin.bots.save') }}</button>
        </div>
    </form>
</div>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script>
        $("#plan-create").validate({
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
