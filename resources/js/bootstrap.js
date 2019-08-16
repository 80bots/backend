window._ = require('lodash');

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    window.Popper = require('popper.js').default;
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');
} catch (e) { }

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo'
import toastr from 'toastr'
window.io = require('socket.io-client');
toastr.options = {
    "hideDuration": "5000",
    "timeOut": "5000",
    "extendedTimeOut": "5000",
};

if (typeof io !== 'undefined') {
/*    var echo = window.Echo = new Echo({
        broadcaster: 'socket.io',
        host: window.location.hostname + ':6001'
    });

    var channel = echo.channel('dispatched-instances.' + window.Laravel.user);

    channel.on('App\\Events\\enqueuedInstanceEvent', function (response) {
        if (response.hasOwnProperty('userInstance') && Object.keys(response.userInstance).length) {
            console.log('data > ', response.userInstance);
            var _id = response.userInstance.id;
            var statusHtml = `<select name="instStatus" class="form-control instStatus" data-id="${_id}">
                            <option value="running">Running</option>
                            <option value="stop">Stop</option>
                            <option value="terminated">Terminate</option>
                        </select>`
            $('.instance-' + _id + ' .tag_user_email').text(response.userInstance.tag_user_email)
            $('.instance-' + _id + ' .tag_name').text(response.userInstance.tag_name)
            $('.instance-' + _id + ' .instanceId').text(response.userInstance.aws_instance_id)
            $('.instance-' + _id + ' .publicIp').text(response.userInstance.aws_public_ip)
            $('.instance-' + _id + ' .statusSelect').html(statusHtml)
            $('.instance-' + _id + ' .loader').removeClass('d-block').addClass('d-none')
        }
        toastr.info('The instance is live now.')
        echo.leave('dispatched-instances.' + window.Laravel.user);
    });

    if (window.Laravel.type === 'Admin') {
        var livechannel = echo.channel('instance-live');
        livechannel.on('App\\Events\\InstanceCreated', function (response) {
            addInstanceToList(response)
        });
    }*/
}

function addInstanceToList(response) {
    if (response.hasOwnProperty('instance') && Object.keys(response.instance).length) {
        var _id = response.instance.id;
        var _aws_public_ip = response.instance.aws_public_ip;
        var _tag_name = response.instance.tag_name;
        var _tag_user_email = response.user.email;
        var _up_time = response.instance.up_time;
        var _aws_instance_id = response.instance.aws_instance_id;
        var _updated_at = response.instance.updated_at;
        var _aws_pem_file_path = response.instance.aws_pem_file_path

        if (table !== 'undefined' || table !== null) {
            var statusHtml = `<select name="instStatus" class="form-control instStatus" data-id="${_id}">
                            <option value="running">Running</option>
                            <option value="stop">Stop</option>
                            <option value="terminated">Terminate</option>
                        </select>`

            var totalHtml = `<tr class="instance-${_id}" role="row">
                    <td class="tag_user_email sorting_1">${_tag_user_email}</td>
                    <td class="tag_name">${_tag_name}</td>
                    <td class="instanceId">${_aws_instance_id}</td>
                    <td class="uptime">${_up_time}</td>
                    <td class="publicIp">${_aws_public_ip}</td>
                    <td class="statusSelect">
                        ${statusHtml}
                    </td>
                    <td>${_updated_at}</td>
                    <td>
                        <a href="${_aws_pem_file_path}" title="Download pem file" download="">
                            <i class="fa fa-download"></i>
                        </a>
                    </td>
                </tr>`

            var jRow = $(totalHtml)
            table.row.add(jRow).draw();
        }
    }
}
