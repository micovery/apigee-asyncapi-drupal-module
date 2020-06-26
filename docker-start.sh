#!/usr/bin/env bash

docker run --rm -it \
          --publish 6060:80 \
          --publish 6063:443 \
          --name apigee-asyncapi-dev-portal \
          -v $(pwd):/drupal/project/web/sites/default/modules/custom/apigee-drupal-asyncapi-module \
          micovery/apigee-asyncapi-dev-portal:latest