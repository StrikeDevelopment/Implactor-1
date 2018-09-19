<?php
/**
*
* 
*  _____                 _            _             
* |_   _|               | |          | |            
*   | |  _ __ ___  _ __ | | __ _  ___| |_ ___  _ __ 
*   | | | '_ ` _ \| '_ \| |/ _` |/ __| __/ _ \| '__|
*  _| |_| | | | | | |_) | | (_| | (__| || (_) | |   
* |_____|_| |_| |_| .__/|_|\__,_|\___|\__\___/|_|   
*                 | |                               
*                 |_|                               
*
* Implactor (c) 2018
* This plugin is licensed under GNU General Public License v3.0!
* It is free to use, copyleft license for software and other 
* kinds of works.
* ------===------
* > Author: Zadezter
* > Team: ImpladeDeveloped
*
*
**/
declare(strict_types=1);
namespace Implactor\tasks;

use Implactor\{Implade, EntityManager};
use Implactor\tasks\ImpladeTask;

class ClearLaggTask extends ImpladeTask {

  protected $plugin;

  public function __construct(Implade $plugin) {
    $this->plugin = $plugin;
  }

  public function onRun(int $currentTick): void {
    $items = EntityManager::getCustom()->clearItems();
    $mobs = EntityManager::getCustom()->clearMobs();
    $this->plugin->getServer()->broadcastMessage(Implade::getInstance()->impladePrefix. Implade::getInstance()->getLang("clear-lagg-message", array(
                  "%items" => $items, 
                  "%mobs" => $mobs
              )));
  }
}
