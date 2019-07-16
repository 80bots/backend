@extends('layouts.app')

@section('title')
    Plans Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        @if(isset($plans) && !empty($plans))
        <div class="card border-bottom-0 rounded-0 rounded-top">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('keywords.prcing_plans') }}</h5>                
            </div>
            <div class="card-body d-flex justify-content-center">
                <div class="row w-75 p-3"> 
                    @foreach($plans as $plan)
                    <div class="col-4">
                        <div class="plancard card shadow text-center w-100 {{ isset($activeplan) && $activeplan->id == $plan->id ? 'activeplan' : (!$subscription_ended ? 'inactiveplans' : '') }}">
                            <div class="card-body">
                                <h5 class="card-title subscription text-uppercase mt-1">
                                    {{!empty($plan->name) ? $plan->name : ''}}
                                </h5>
                                <h5 class="card-subtitle subscription text-blue price mb-2 font-weight-bold mt-4">
                                    {{!empty($plan->price) ? config('app.currency_symbol') . $plan->price : ''}}
                                </h6>
                                <h6 class="subscription mb-2 text-muted mt-3">
                                    {{!empty($plan->credit) ? $plan->credit : ''}}
                                    {{ __('keywords.credits.plural') }}
                                </h6>
                            </div>
                            <div class="card-footer">
                                <button {{ isset($activeplan) && $activeplan->id == $plan->id ? 'disabled' : '' }} class="mb-2 plan-btn mt-1 btn btn-primary btn-round text-uppercase" data-plan_id="{{!empty($plan->stripe_plan) ? $plan->stripe_plan : ''}}">
                                    {{ __('keywords.subscribe-btn-text') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif   


        <div class="card border-top-0 rounded-0 rounded-bottom">
            <div class="card-body d-flex justify-content-center">
                <div class="row w-100 p-3">
                    @include('layouts.imports.messages')
                    @if(isset($user) && is_null($user->stripe_id))
                        <form action="{{ route('user.subscription.create') }}" method="post" class="w-100" id="payment-form">
                            @csrf
                            <div class="offset-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="customer_name">Name*</label>
                                    <input type="text" value="{{ old('customer_name') }}" id="customer_name" name="customer_name" class="form-control">
                                </div>
                            </div>
                            <div class="offset-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="card_number">Credit Card Number*</label>
                                    <input type="text" value="{{ old('number') }}" id="card_number" name="number" maxlength="16" class="form-control"/>
                                </div>
                            </div>
                            <div class="offset-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="card_month">Expiry Month*</label>
                                    <input type="text" value="{{ old('month') }}" id="card_month" name="month" class="form-control"/>
                                </div>
                            </div>
                            <div class="offset-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="card_year">Expiry Year*</label>
                                    <input type="text" value="{{ old('year') }}" id="card_year" name="year" class="form-control"/>
                                </div>
                            </div>
                            <div class="offset-3 col-md-6 col-sm-12">
                                <div class="form-group">
                                    <label for="card_cvv">CVV*</label>
                                    <input type="text" value="{{ old('cvv') }}" id="card_cvv" name="cvv" class="form-control"/>
                                </div>
                            </div>
                            <input type="hidden" id="plan_id" value="{{ old('plan_id') }}" name="plan_id"/>
                            <div class="offset-3 col-md-6 col-sm-12">
                                <input type="submit" form="payment-form" class="btn btn-primary" onclick="return confirmSubscription(event);"/>
                            </div>
                        </form>
                    @else
                        <form action="{{ route('user.subscription.swap') }}" method="post" class="w-100" id="payment-form">
                            @csrf
                            <input type="hidden" id="plan_id" value="{{ old('plan_id') }}" name="plan_id"/>
                            <div class="offset-5 col-md-1 col-sm-12">
                                <input type="submit" form="payment-form" class="btn btn-primary" onclick="return confirmSwitch(event);"/>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function confirmSwitch(e) {
            e.preventDefault()
            $_plan_id = document.getElementById("plan_id").value
            if($_plan_id !== '') {
                bootbox.confirm({
                    size: "small",
                    title: "Change Subscription",
                    message: "Are you sure you want to change from your current plan?",
                    callback: function(result) {
                        if(result) {
                            document.getElementById("payment-form").submit()
                        }
                        return true
                    }
                })
                return true
            } else {
                bootbox.alert({
                    size: "small",
                    title: "Change Subscription",
                    message: "You need to select a plan.",
                })
            }
        }

        function confirmSubscription(e) {
            e.preventDefault()
            $_plan_id = document.getElementById("plan_id").value
            if($_plan_id !== '') {
                bootbox.confirm({
                    size: "small",
                    title: "Subscription",
                    message: "Are you sure you want to subscribe to this plan?",
                    callback: function(result) {
                        if(result) {
                            document.getElementById("payment-form").submit()
                        }
                        return false
                    }
                })
                return true
            } else {
                bootbox.alert({
                    size: "small",
                    title: "Change Subscription",
                    message: "You need to select a plan.",
                })
            }
        }

        $(document).ready(function() {
            $('.plan-btn').click(function(e){
                var Obj = $(this)
                e.preventDefault()
                $('.plan-btn').removeClass('btn-success').addClass('btn-primary')
                $(this).removeClass('btn-primary').addClass('btn-success')
                $("#plan_id").val(Obj.data('plan_id'))
            });
        });
    </script>
@endsection
