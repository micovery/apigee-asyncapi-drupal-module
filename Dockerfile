FROM micovery/snownow-dev-portal

USER root
RUN cd /drupal/project && \
    sudo -u drupal composer config repositories.repo-name vcs git@github.com:micovery/apigee-asyncapi-drupal-module.git && \
    sudo -u drupal composer require micovery/apigee-asyncapi-drupal-module:dev-master && \
    sudo -u drupal composer clear-cache && \
    \
    service mysql start && \
    cd /drupal/project && \
    drush en apigee_drupal8_asyncapi

USER drupal