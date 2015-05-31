#!/bin/bash

# Install composer
php -r "readfile('https://getcomposer.org/installer');" | php

# Install dependencies
php composer.phar install

# Make hook executable
chmod 755 main.php

# Add hook to cPanel
/usr/local/cpanel/bin/manage_hooks add script ./main.php
