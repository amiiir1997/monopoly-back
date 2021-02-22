<head>
  <title>Pusher Test</title>
  <meta name ="csrf-token" content = '{{ csrf_token()}}'>
</head>
<body>
  <p>
    Hello
  </p>
  <script type="module" src="{{ asset('js/app.js') }}"></script>
  <script>
    Echo.channel('my-channel').listen('Newmassage', (e) => {
        console.log(e.massage)
        });
  </script>
</body>