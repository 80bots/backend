$(document).ready(function () {
    //select2 dropdown initialize
    $('.select2').select2();
})

$(document).on('click', '.sidebar-toggle', function () {
    $('.sidebar').toggleClass('closed');
    $(this).toggleClass('closed');
});

$(window).on('resize', function () {
    if (window.matchMedia("(max-width: 992px)").matches && !$('.sidebar').hasClass('closed')) {
        $('.sidebar, .sidebar-toggle').addClass('closed');
    } else if ($('.sidebar').hasClass('closed')) {
        $('.sidebar, .sidebar-toggle').removeClass('closed');
    }
})

$(window).on('load', function () {
    if (window.matchMedia("(max-width: 992px)").matches) {
        if (!$('.sidebar, .sidebar-toggle').hasClass('closed')) {
            $('.sidebar').addClass('closed');
        }
    }
})