<?php
// Copyright 2019 Google LLC
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//      http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

namespace Drupal\apigee_drupal8_asyncapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\Component\Utility\UrlHelper;
use \Drupal\Component\Utility\Crypt;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

module_load_include('inc', 'apigee_drupal8_asyncapi', 'src/apigee_drupal8_asyncapi.constants');
module_load_include('inc', 'apigee_drupal8_asyncapi', 'src/apigee_drupal8_asyncapi.functions');


class AsyncAPIController extends ControllerBase {

  public static function get(&$var, $default=null) {
    return isset($var) ? $var : $default;
  }


  protected function loadAsyncAPI() {
    $config = asyncapi_get_module_settings()->get(ASYNCAPI_MODULE_CONFIG_ROOT);

    $specFileId = $config[ASYNCAPI_SPEC_FILE_ID_VAR];
    $specFileURL = "";
    if (!empty($specFileId)) {
      $specFile = \Drupal\file\Entity\File::load($specFileId);
      $specFileURL = $specFile->url();
    }

    $output = array(
      'react_app_container' => array(
        '#type' => 'markup',
        '#markup' =>  '<div id="master-container" ></div>',
        '#attached' => [
          'library' => [
            ASYNCAPI_MODULE_NAME.'/asyncapi',
            ASYNCAPI_MODULE_NAME.'/asyncapi-css'
          ],
          'drupalSettings' => [
            'asyncapi' => [
              'spec-url' => $this->get($specFileURL, '')
            ]
          ]
        ],
      ),
    );

    return $output;
  }

  public function content() {
    $build = $this->loadAsyncAPI();
    return $build;
  }


  public function access() {
    $config = asyncapi_get_module_settings()->get(ASYNCAPI_MODULE_CONFIG_ROOT);
    $access_requirement = $this->get($config[ASYNCAPI_ACCESS_VAR], ASYNCAPI_ACCESS_EVERYONE);

    if ($access_requirement == ASYNCAPI_ACCESS_EVERYONE) {
      return AccessResult::allowed();
    }

    if ($access_requirement == ASYNCAPI_ACCESS_LOGGED_IN && \Drupal::currentUser()->isAuthenticated()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }
}