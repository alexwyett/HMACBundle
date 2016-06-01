# HMAC Annotation bundle

Add `new HMACBundle\HMACBundle()` into your AppKernel.php

Run `app/console doctrine:schema:update`

Add new parameters for the two bundle settings:

* hmac, boolean setting.  Defaulted to false
* hmac_roles, string array of roles

For example in parameters.yml:

`
parameters:
    hmac: 
        hmac: true
        hmac_roles: ["USER", "ADMIN"]
`