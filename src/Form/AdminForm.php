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
namespace Drupal\apigee_drupal8_asyncapi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;


module_load_include('inc', 'apigee_drupal8_asyncapi', 'src/apigee_drupal8_asyncapi.constants');
module_load_include('inc', 'apigee_drupal8_asyncapi', 'src/apigee_drupal8_asyncapi.functions');

class AdminForm extends ConfigFormBase {


  public function getFormId() {
    return 'apigee_drupal8_asyncapi_admin_settings';
  }

  protected function getEditableConfigNames() {
    return [
      ASYNCAPI_MODULE_SETTINGS_VAR
    ];
  }

  public static function get(&$var, $default=null) {
    return isset($var) ? $var : $default;
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = asyncapi_get_module_settings()->get(ASYNCAPI_MODULE_CONFIG_ROOT);

    $form['asyncapi'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AsyncAPI')
    ];

    $form['asyncapi'][ASYNCAPI_SPEC_FILE_VAR] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Spec'),
      '#description' => $this->t('Upload YAML file containing the AsyncAPI spec (2.0.0).'),
      '#upload_validators' => [
        'file_validate_extensions' => ['yaml yml json'],
      ],
      '#upload_location' => 'public://asyncapi_specs/',
      '#default_value' => array($this->get($config[ASYNCAPI_SPEC_FILE_ID_VAR], null))
    ];

    $form['asyncapi'][ASYNCAPI_PATH_VAR] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('Path within Drupal where the AsyncAPI doc is accessible. (Note that changing the path, will clear the Drupal cache)'),
      '#default_value' => $this->get($config[ASYNCAPI_PATH_VAR], ASYNCAPI_PATH_DEFAULT),
    ];


    $form['asyncapi'][ASYNCAPI_ACCESS_VAR] = [
      '#type' => 'radios',
      '#title' => $this->t('Access'),
      '#description' => $this->t('Which users can view the AsyncAPI page'),
      '#options' => ['everyone' => $this->t('Everyone'), 'logged_in' => $this->t('Logged-in Users')],
      '#default_value' => $this->get($config[ASYNCAPI_ACCESS_VAR], 'everyone'),
    ];

    $form['asyncapi'][ASYNCAPI_MENU_LINK_ENABLED_VAR] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show AsyncAPI link in the main menu'),
      '#default_value' => $this->get($config[ASYNCAPI_MENU_LINK_ENABLED_VAR], 1),
    ];

    $form['asyncapi'][ASYNCAPI_MENU_LINK_TITLE_VAR] = [
      '#type' => 'textfield',
      '#title' => $this->t('Menu menu link text'),
      '#description' => $this->t('Text to display for link in the main menu'),
      '#default_value' => $this->get($config[ASYNCAPI_MENU_LINK_TITLE_VAR], 'AsyncAPI'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $configs = asyncapi_save_config_values($form_state->getValues(), $form_state);
    $this->updateMenu($configs);

    parent::submitForm($form, $form_state);
  }

  protected function updateMenu($configs) {
    $this->handleMainMenuUpdate($configs);
    $this->clearCacheIfPathChanged($configs);

  }

  function handleMainMenuUpdate($configs) {
    if (!$configs['new'][ASYNCAPI_MENU_LINK_ENABLED_VAR]) {
      asyncapi_delete_main_menu();
      return;
    }

    $new_title = $configs['new'][ASYNCAPI_MENU_LINK_TITLE_VAR];
    $new_path = $configs['new'][ASYNCAPI_PATH_VAR];

    $old_title = $configs['old'][ASYNCAPI_MENU_LINK_TITLE_VAR];
    $old_path = $configs['old'][ASYNCAPI_PATH_VAR];

    $uuid = asyncapi_get_main_menu_id() ?: "";

    $uuid = asyncapi_menu_link_save($uuid, $new_title, $new_path);

    asyncapi_set_main_menu_id($uuid);
  }

  protected function clearCacheIfPathChanged($configs): void
  {
    if ($configs['old'][ASYNCAPI_PATH_VAR] != $configs['new'][ASYNCAPI_PATH_VAR]) {
      //We need to clear the cache so that the menu is rebuilt
      //cache_clear_all('*', 'cache_page', TRUE);
      \Drupal::cache('menu')->invalidateAll();
      drupal_flush_all_caches();
      drupal_set_message('Caches cleared');
    }
  }

}

