@extends('layouts.app')

@section('title')
Scheduling instances edit
@endsection

@section('css')

@endsection

@section('content')
   <div class="wrapper">
    <form class="card" id="scheduling_update" action="{{route('user.scheduling.update',$id)}}" method="post">
        @method('PATCH')
            @csrf
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">Add Bot</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        <label for="">Select Instance*</label>
                        <select name="user_instances_id" id="user_instances_id" class="form-control">
                            <option>Select Instance </option>
                            @foreach($instances as $row)
                            <option value="{{$row->id}}"> {{$row->aws_instance_id}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">Start time*</label>
                        <input type="text" value="{{isset($scheduling->start_time) ? $scheduling->start_time : ''}}" name="start_time" class="form-control"/>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        <label for="">End time*</label>
                        <input type="text" value="{{isset($scheduling->end_time) ? $scheduling->end_time : ''}}"  name="end_time" class="form-control"/>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary btn-round">Update</button>
        </div>
    </form>
</div>

@endsection


@section('script')
    <script src="{{ asset('js/jquery.validate.min.js')  }}" type="text/javascript"></script>
    <script>
        $("#scheduling_update").validate({
            rules: {
                user_instances_id: {
                    required: true
                },
                start_time: {
                    required: true
                },
                end_time: {
                    required: true
                }
            }
        });
    </script>
@endsection
