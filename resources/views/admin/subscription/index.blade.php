@extends('layouts.app')

@section('title')
    Subscription Plan Listing
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Plan List</h5>
                {{--<a href="{{route('user.instance.create')}}" class="btn btn-round btn-primary"><i class="fas fa-plus"></i> Add Instance</a>--}}
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="plan-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>Plan Name</th>
                            <th>Price</th>
                            <th>Credits</th>
                            <th>Status</th>
                            <th>Action</th>
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
                                                    onclick="ChangeStatus('{{$plan->id}}','inactive')"
                                                    title="make it inactive">Active
                                            </button>
                                        @else
                                            <button type="button" class="form-group btn btn-danger mb-0"
                                                    onclick="ChangeStatus('{{$plan->id}}','active')"
                                                    title="make it active">Inactive
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.plan.destroy',$plan->id) }}" method="POST">
                                            <div class="d-flex align-items-center">
                                                {{--<a href="{{route('admin.plan.show',$plan->id)}}"
                                                   class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                   title="Show Plan"><i class="fa fa-eye"></i></a>--}}

                                                <a href="{{route('admin.plan.edit',$plan->id)}}"
                                                   class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                   title="Edit Plan"><i class="fa fa-edit"></i></a>

                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('Are you sure?')" class="form-group btn btn-icon btn-danger change-credit-model mb-0"><i class="fa fa-trash"></i></button>
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

        function ChangeStatus(planId, status) {
            var URL = '{{route('admin.plan.change-status')}}';
            $.ajax({
                type: 'post',
                url: URL,
                cache: false,
                data: {
                    _token: function () {
                        return '{{csrf_token()}}';
                    },
                    id: planId,
                    status: status
                },
                success: function (data) {
                    location.reload();
                }
            });
        }
    </script>
@endsection
