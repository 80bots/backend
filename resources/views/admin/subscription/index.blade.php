@extends('layouts.app')

@section('title')
    {{ __('admin.subscription.list') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('admin.subscription.list') }}</h5>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="plan-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('admin.subscription.plan_name') }}</th>
                            <th>{{ __('admin.subscription.price') }}</th>
                            <th>{{ __('admin.subscription.credit') }}</th>
                            <th>{{ __('admin.subscription.status') }}</th>
                            <th>{{ __('admin.subscription.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if(isset($planListObj) && !empty($planListObj))
                            @foreach($planListObj as $plan)
                                <tr>
                                    <td>{{!empty($plan->name) ? $plan->name : ''}}</td>
                                    <td>{{!empty($plan->price) ? $plan->price : ''}}</td>
                                    <td>{{!empty($plan->credit) ? $plan->credit : 0.0}}</td>
                                    <td>
                                        @if(!empty($plan->status) && $plan->status == 'active')
                                            <button type="button" class="form-group btn btn-success mb-0"
                                                onclick="ChangeStatus('{{route('admin.subscription.update.status', $plan->id)}}', 'inactive')"
                                                title="make it inactive">
                                                {{ __('admin.subscription.statuses.active') }}
                                            </button>
                                        @else
                                            <button type="button" class="form-group btn btn-danger mb-0"
                                                onclick="ChangeStatus('{{route('admin.subscription.update.status', $plan->id)}}', 'active')"
                                                title="make it active">
                                                {{ __('admin.subscription.statuses.inactive') }}
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.subscription.destroy', $plan->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <div class="d-flex align-items-center">
                                                <a href="{{route('admin.subscription.edit',$plan->id)}}"
                                                   class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                   title="{{ __('admin.subscription.edit_plan') }}">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <button class="form-group btn btn-icon btn-danger change-credit-model mb-0"
                                                    type="submit" onclick="return confirm('{{ __('keywords.are_you_sure') }}')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
    <script>
        $(document).ready(function () {
            $('#plan-list').DataTable();
        });

        function ChangeStatus(url, status) {
            $.ajax({
                type: 'PUT',
                url: url,
                cache: false,
                data: {
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        }
    </script>
@endsection
