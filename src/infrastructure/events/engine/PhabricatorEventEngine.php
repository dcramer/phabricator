<?php

/*
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class PhabricatorEventEngine {

  private static $instance;

  private $listeners = array();

  private function __construct() {
    // <empty>
  }

  public static function initialize() {
    self::$instance = new PhabricatorEventEngine();

    // Instantiate and register custom event listeners so they can react to
    // events.
    $listeners = PhabricatorEnv::getEnvConfig('events.listeners');
    foreach ($listeners as $listener) {
      id(new $listener())->register();
    }
  }

  public static function getInstance() {
    if (!self::$instance) {
      throw new Exception("Event engine has not been initialized!");
    }
    return self::$instance;
  }

  public function addListener(
    PhabricatorEventListener $listener,
    $type) {
    $this->listeners[$type][] = $listener;
    return $this;
  }

  public static function dispatchEvent(PhabricatorEvent $event) {
    $instance = self::getInstance();

    $listeners = idx($instance->listeners, $event->getType(), array());
    foreach ($listeners as $listener) {
      if ($event->isStopped()) {
        // Do this first so if someone tries to dispatch a stopped event it
        // doesn't go anywhere. Silly but less surprising.
        break;
      }
      $listener->handleEvent($event);
    }
  }

}