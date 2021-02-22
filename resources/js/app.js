require('./bootstrap');

import Echo from 'laravel-echo';

window.Pusher = require ('pusher-js');
    window.Echo = new Echo({
      broadcaster : 'pusher',
      key : '123456',
      cluster : 'mt1',
      wsHost : '127.0.0.1',
      wsPort :6001
    });
