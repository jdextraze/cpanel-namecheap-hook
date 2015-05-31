#!/bin/bash

# Remove hook from cPanel
/usr/local/cpanel/bin/manage_hooks del script ./main.php

# Remove executable bit from hook
chmod 644 main.php

# Remove dependencies
rm -Rf vendor

# Remove composer
rm -f composer.phar
