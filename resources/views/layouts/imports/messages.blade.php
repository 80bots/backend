@if ($message = session('success'))
    <div class="alert alert-success fade show" role="alert">
        <strong>{{ $message }}</strong>
    </div>
@endif

@if ($message = session('error'))
    <div class="alert alert-danger fade show" role="alert">
        <strong>{{ $message }}</strong>
    </div>
@endif

@if ($message = session('warning'))
    <div class="alert alert-warning fade show" role="alert">
        <strong>{{ $message }}</strong>
    </div>
@endif

@if ($message = session('info'))
    <div class="alert alert-info fade show" role="alert">
        <strong>{{ $message }}</strong>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger w-100">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<script type="text/javascript">
    setTimeout(()=>{
        $('.alert').fadeOut();
    },10000)
</script>
