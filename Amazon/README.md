Opauth-amazon
=============
[Opauth][1] strategy for Amazon authentication.

Implemented based on http://login.amazon.com/website

Full login with Amazon developer guide here: 'https://images-na.ssl-images-amazon.com/images/G/01/lwa/dev/docs/website-developer-guide._TTH_.pdf'

Getting started
----------------
1. Install Opauth-Amazon:
   ```bash
   cd path_to_opauth/Strategy
   git clone https://github.com/tomcb/opauth-amazon.git amazon
   ```

2. Register application at http://login.amazon.com/manageApps
   - Allowed Returl URLs needs to include 'https://path_to_Opauth/amazon/int_callback'
   - The Amazon login button images are available here: "http://login.amazon.com/button-guide"

3. Configure Opauth-amazon strategy with at least `Client ID` and `Client Secret`.

4. Edit callback.php in opauth-master/example to handle the values returned by Amazon.

5. Direct user to `http://path_to_opauth/amazon` to authenticate

Strategy configuration
----------------------

Required parameters:

```php
<?php
'amazon' => array(
	'client_id' => 'YOUR APP ID',
	'client_secret' => 'YOUR APP SECRET'
    'scope' => 'profile|postal_code|profile postal_code'
)
```

Scope is optional, the possible values are 'profile', 'postal_code' or 'profile postal_code'. 'profile' gets you the user's name, email and user id, as you might expect, 'postal_code' gets you the user's postal or zip code.

License
---------
Opauth-amazon is MIT Licensed  
Copyright © 2013 Omlet Ltd.

[1]: https://github.com/opauth/opauth
