# Set an alias for PHP Unit.
alias phpunit="/var/www/html/vendor/bin/phpunit --bootstrap /var/www/html/web/core/tests/bootstrap.php"
alias phpunit-c="/var/www/html/vendor/bin/phpunit --bootstrap /var/www/html/web/core/tests/bootstrap.php -c /var/www/html/web/core"

# Set aliases for PHPCS and defaults.
alias phpcs="/var/www/html/vendor/bin/phpcs"
alias phpcs-d="/var/www/html/vendor/bin/phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml --ignore=node_modules,bower_components,vendor -p --colors"
if [[ -f "/var/www/html/vendor/bin/phpcs" ]]; then
  /var/www/html/vendor/bin/phpcs --config-set default_standard Drupal
  /var/www/html/vendor/bin/phpcs --config-set show_progress 1
  /var/www/html/vendor/bin/phpcs --config-set colors 1
fi

# Alias drush to ease CLI debugging.
alias drush="/var/www/html/vendor/bin/drush --root=/var/www/html/web"
