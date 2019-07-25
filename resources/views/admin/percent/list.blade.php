@extends('layouts.app')

@section('title')
    {{ __('admin.percent.low_credit_notification') . ' '. __('keywords.list') }}
@endsection

@section('css')

@endsection

@section('content')
    <div class="wrapper">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">{{ __('admin.percent.low_credit_notification') . ' '. __('keywords.list') }}</h5>
                <h5 class="mb-0"><a href="{{ route('admin.percent.create') }}" class="btn btn-round btn-primary"><i class="fas fa-plus-circle"></i></a></h5>
            </div>
            <div class="card-body">
                @include('layouts.imports.messages')
                <div class="table-responsive">
                    <table id="percentage-list" class="table thead-default vertical-middle mb-0">
                        <thead>
                        <tr>
                            <th>{{ __('admin.percent.id') }}</th>
                            <th>{{ __('admin.percent.percentage') }}</th>
                            <th>{{ __('admin.percent.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php $i = 1; ?>
                        @if(isset($percentages) && !empty($percentages))
                            @foreach($percentages as $percentage)
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>{{!empty($percentage->percentage) ? $percentage->percentage : ''}} %</td>
                                    <td>
                                        <form action="{{ route('admin.percent.destroy',$percentage->id) }}" method="POST">
                                            <div class="d-flex align-items-center">
                                                {{--<a href="{{route('admin.plan.show',$percentage->id)}}"
                                                   class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                   title="Show Plan"><i class="fa fa-eye"></i></a>--}}

                                                <a href="{{route('admin.percent.edit',$percentage->id)}}"
                                                   class="form-group btn btn-icon btn-primary change-credit-model mb-0 mr-1"
                                                   title="{{ __('admin.percent.edit_plan') }}"><i class="fa fa-edit"></i></a>

                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm(__('keywords.are_you_sure'))" class="form-group btn btn-icon btn-danger change-credit-model mb-0"><i class="fa fa-trash"></i></button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                                <?php $i++; ?>
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
            $('#percentage-list').DataTable();
        });

    </script>
@endsection
