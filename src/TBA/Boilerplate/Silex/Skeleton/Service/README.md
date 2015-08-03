Silex TBA Provider (for ramcoelho/silex-skeleton)
===

## Usage:

### Configure your bootstrap with this settings:

```php

$app['tba.table_name'] = 'your_table_name';
$app['tba.user_field'] = 'your_username_or_email_field_name';
$app['tba.pass_field'] = 'your_password_field_name';
$app['tba.token_timeout'] = 'your_token_timeout_in_minutes';


```

### Set your PDO connection

```php

$myPDOConnection = new \PDO(...);

//or

$myPDOConnection = $app['db']; //Using DBProvider (silex skeleton provider)

$app['tba']->setConnection( $myPDOConnection );

```

### And finally...

```php

$app->register(new TBAProvider());

```