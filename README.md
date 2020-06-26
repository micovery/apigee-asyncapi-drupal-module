## Apigee Drupal AsyncAPI Module

This repo contains a Drupal 8 module. The module allows you to embed the AsyncAPI react component into a Drupal page.

The component that ships with this module is a web-packed version of the original [AsyncAPI React component](https://github.com/asyncapi/asyncapi-react) by [Kyma](https://kyma-project.io/).

Go over to the [apigee-asyncapi-react](https://github.com/micovery/apigee-asyncapi-react) repo to see the source of the web-packed component.

### How to install it (with composer and drush)

1. First install the module:
    ```bash
    $ composer config repositories.repo-name vcs git@github.com:micovery/apigee-asyncapi-drupal-module.git
    $ composer require micovery/apigee-asyncapi-drupal-module:dev-master
    ```

2. Then enable it
    ```bash
    $ drush en apigee_drupal8_asyncapi
    ```


### Local Development

If you made changes in the docker file, rebuild the docker image

```shell script
./docker-build.sh
```

Then, start the docker container:

```shell script
 docker run --rm -it \
            --publish 6060:80 \
            --publish 6063:443 \
            --name apigee-asyncapi-dev-portal \
            -v $(pwd):/drupal/project/web/sites/default/modules/custom/apigee-drupal-asyncapi-module \
            micovery/apigee-asyncapi-dev-portal:latest
```

This setup allows you to access the Drupal portal locally at [http://localhost:6060](http://localhost:6060).
Any changes you make in your local file system should reflect the Drupal site.

### Not Google Product Clause

This is not an officially supported Google product.