$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    //select2 dropdown initialize
    $('.select2').select2();
})

$(document).on('click', '.sidebar-toggle', function () {
    $('.sidebar').toggleClass('closed');
    $(this).toggleClass('closed');
});

$(window).on('resize', function () {
    if(window.screen.width > 1024) {
      if (window.matchMedia("(max-width: 992px)").matches && !$('.sidebar').hasClass('closed')) {
        $('.sidebar, .sidebar-toggle').addClass('closed');
      } else if ($('.sidebar').hasClass('closed')) {
        $('.sidebar, .sidebar-toggle').removeClass('closed');
      }
    }
})

$(window).on('load', function () {
    if (window.matchMedia("(max-width: 992px)").matches) {
        if (!$('.sidebar, .sidebar-toggle').hasClass('closed')) {
            $('.sidebar, .sidebar-toggle').addClass('closed');
        }
    }
})
const $menu = $('.sidebar');

$(document).mouseup(function (e) {
   if (window.screen.width < 1024
   && !$menu.is(e.target)
   && !$menu.has(e.target).length && !$(e.target).closest('.sidebar-toggle').length)  {
       $('.sidebar, .sidebar-toggle').addClass('closed');
   }
});

 $('.sync-instances').click(function() {
    let _targetURL = $(this).attr('data-href');
    $('#sync-loader').toggleClass('hidden');
    $('#instance-div').toggleClass('hidden');
    window.location.href =  _targetURL;
 });
