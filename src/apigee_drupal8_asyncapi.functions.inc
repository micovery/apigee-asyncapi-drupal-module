<?php

// Copyright 2020 Google LLC
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

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\file\Entity\File;



function asyncapi_info($value) {
  \Drupal::logger(ASYNCAPI_MODULE_NAME)->info(var_export($value, true));
}

function asyncapi_notice($value) {
  \Drupal::logger(ASYNCAPI_MODULE_NAME)->notice(var_export($value, true));
}

function asyncapi_get_module_settings() {
  return \Drupal::service('config.factory')->getEditable(ASYNCAPI_MODULE_SETTINGS_VAR);
}

function asyncapi_cfg_set($var, $val) {
  $settings = asyncapi_get_module_settings();
  $config = $settings->get(ASYNCAPI_MODULE_CONFIG_ROOT);
  $config[$var] = $val;
  $settings->set(ASYNCAPI_MODULE_CONFIG_ROOT, $config)->save();
  return $val;
}

function asyncapi_cfg_get($var) {
  $settings = asyncapi_get_module_settings();
  $config = $settings->get(ASYNCAPI_MODULE_CONFIG_ROOT);
  $val = isset($config[$var])?$config[$var]:null;
  return $val;
}

function asyncapi_cfg_del($var) {
  $settings = asyncapi_get_module_settings();
  $config = $settings->get(ASYNCAPI_MODULE_CONFIG_ROOT);
  $val = isset($config[$var])?$config[$var]:null;

  $settings->set(ASYNCAPI_MODULE_CONFIG_ROOT, $config)->save();
  return $val;
}

function asyncapi_set_default_values() {
  $config = array(
    ASYNCAPI_MENU_LINK_TITLE_VAR => 'AsyncAPI',
    ASYNCAPI_MENU_LINK_ENABLED_VAR => 0,
    ASYNCAPI_ACCESS_VAR => ASYNCAPI_ACCESS_EVERYONE,
    ASYNCAPI_PATH_VAR => ASYNCAPI_PATH_DEFAULT,
    ASYNCAPI_MENU_ID_VAR => "",
    ASYNCAPI_SPEC_FILE_NAME_VAR => "",
    ASYNCAPI_SPEC_FILE_ID_VAR => null
  );

  asyncapi_get_module_settings()->set(ASYNCAPI_MODULE_CONFIG_ROOT, $config)->save();
}

function asyncapi_save_config_values($values, $form_state) {
  $settings = asyncapi_get_module_settings();

  $old_config = $settings->get(ASYNCAPI_MODULE_CONFIG_ROOT);

  list($fileName, $fileId) = asyncapi_save_or_delete_spec_file($old_config, $form_state);

  $new_config = array(
    ASYNCAPI_MENU_LINK_TITLE_VAR => $values[ASYNCAPI_MENU_LINK_TITLE_VAR],
    ASYNCAPI_MENU_LINK_ENABLED_VAR => $values[ASYNCAPI_MENU_LINK_ENABLED_VAR],
    ASYNCAPI_ACCESS_VAR => $values[ASYNCAPI_ACCESS_VAR],
    ASYNCAPI_PATH_VAR => $values[ASYNCAPI_PATH_VAR],
    ASYNCAPI_SPEC_FILE_NAME_VAR => $fileName,
    ASYNCAPI_SPEC_FILE_ID_VAR => $fileId
  );

  $merged_config = array_merge($old_config, $new_config);
  $settings->set(ASYNCAPI_MODULE_CONFIG_ROOT, $merged_config)->save();

  return array( 'new' => $merged_config, 'old' => $old_config);
}


function asyncapi_save_or_delete_spec_file($old_config, $form_state): array {
  $oldFileId = $old_config[ASYNCAPI_SPEC_FILE_ID_VAR];
  $newFileId = $form_state->getValue([ASYNCAPI_SPEC_FILE_VAR, 0]);

  // delete old file if necessary
  if (!empty($oldFileId) && ($oldFileId != $newFileId)) {
    $oldFile = File::load($oldFileId);
    $oldFile->delete();
  }

  // add new file
  $fileName = "";
  $fileId = null;

  if (!empty($newFileId)) {
    $newFile = File::load($newFileId);
    $newFile->setPermanent();
    $newFile->save();

    $fileName = $newFile->getFileName();
    $fileId = $newFileId;
  }
  return array($fileName, $fileId);
}

function asyncapi_get_main_menu_id() {
  return asyncapi_cfg_get(ASYNCAPI_MENU_ID_VAR);
}

function asyncapi_set_main_menu_id($id) {
  asyncapi_cfg_set(ASYNCAPI_MENU_ID_VAR, $id);
}

function asyncapi_clear_main_menu_id() {
  asyncapi_cfg_del(ASYNCAPI_MENU_ID_VAR);
}

function asyncapi_delete_main_menu() {
  $uuid = asyncapi_get_main_menu_id();

  if (!$uuid) {
    return;
  }


  asyncapi_menu_link_delete($uuid);
  asyncapi_clear_main_menu_id();
}

function asyncapi_menu_link_delete($uuid) {
  $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['uuid' => $uuid]);
  $menu_link = reset($menu_links);

  if (!$menu_link) {
    return;
  }
  $menu_link->delete();
}


function asyncapi_menu_link_save($uuid, $new_title, $new_path) {
  $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['uuid' => $uuid]);
  $menu_link = reset($menu_links);

  if ($menu_link) {
    $menu_link->title = $new_title;
    $menu_link->link = ['uri' => "internal:/" . $new_path];
  }
  else {
    $menu_link = MenuLinkContent::create([
      'title' => $new_title,
      'link' => ['uri' => "internal:/" . $new_path],
      'menu_name' => 'main',
      'expanded' => TRUE
    ]);
  }

  $menu_link->save();
  return $menu_link->uuid();

}

function asyncapi_dis_module() {
  asyncapi_delete_main_menu();
}

function asyncapi_en_module() {
  asyncapi_set_default_values();
}

function asyncapi_access() {
  if (\Drupal::state()->get(ASYNCAPI_ACCESS_VAR, ASYNCAPI_ACCESS_EVERYONE) == ASYNCAPI_ACCESS_LOGGED_IN) {
    return user_is_logged_in();
  }

  return TRUE;
}