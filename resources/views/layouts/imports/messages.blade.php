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

<script type="text/javascript">
    setTimeout(()=>{
        $('.alert').fadeOut();
    },7000)
</script>
