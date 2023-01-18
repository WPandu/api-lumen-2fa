<html>
  <head>
    <title>reCAPTCHA demo: Simple page</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  </head>
  <body>
    <form action="?" method="POST">
      <div class="g-recaptcha" data-sitekey="{{env('GOOGLE_RECAPTCHA_KEY')}}" data-callback="onSuccess"></div>
    </form>
    <script type="text/javascript">
    var onSuccess = function(response) {
        console.log(response)
    }
    </script>
  </body>
</html>
