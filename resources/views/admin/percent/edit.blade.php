@extends('layouts.app')

@section('title')
    {{ __('keywords.edit') . ' ' .__('admin.percent.low_credit_notification') }}
@endsection

@section('css')

@endsection

@section('content')
<div class="wrapper">
    <form class="card" id="percentage-create" action="{{route('admin.percent.update',['id' => $percentage->id])}}" method="POST">

        @method('PUT')
        @csrf
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">{{ __('keywords.edit') . ' ' .__('admin.percent.low_credit_notification') }}</h5>
        </div>
        <input type="hidden" name="id" value="{{$percentage->id}}">
        <div class="card-body">
            <div class="row">
                <div class="offset-3 col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">{{ __('admin.percent.select_percentage') }}*</label>
                        <select  name="percentage" class="form-control">
                            <option value="">-- {{ __('admin.percent.select_percentage') }} -- </option>
                            <?php $i = 1; ?>
                                @for($i ; $i <= 100 ; $i++) 
                                    @if($percentage->percentage == $i)
                                        <option value="{{$i}}" selected="true">{{$i}} %</option>
                                    @else
                                        <option value="{{$i}}" >{{$i}} %</option>
                                    @endif
                                @endfor
                            <?php ?>
                        </select>
                        @if($errors->has('percentage'))
                            <p class="error">{{ $errors->first('percentage') }}</p>
                        @endif
                    </div>
                </div>
                
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary btn-round">{{ __('keywords.update') }}</button>
        </div>
    </form>
</div>
@endsection

@section('script')
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script>
        $("#percentage-create").validate({
            rules: {
                percentage: {
                    required: true
                },
            }
        });
    </script>
@endsection
