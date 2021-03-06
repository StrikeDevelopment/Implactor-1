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

use pocketmine\Player;
use pocketmine\entity\Entity;

use Implactor\Implade;
use Implactor\entities\DeathHuman;
use Implactor\tasks\ImpladeTask;
use Implactor\particles\DespawnParticles;

class DeathHumanDespawnTask extends ImpladeTask {

  protected $plugin;
  private $entity;
  private $player;

  public function __construct(Implade $plugin, Entity $entity, Player $player) {
    $this->plugin = $plugin;
    $this->entity = $entity;
    $this->player = $player;
  }

  public function onRun(int $currentTick): void {
    $player = $this->player;
    $entity = $this->entity;
    $plugin = $this->plugin;
    if ($entity instanceof DeathHuman) {
      if ($entity->getNameTag() === "§7[§c". Implade::getInstance()->getLang("death-nametag") ."§7]§r\n§f" . $player->getName()) $entity->close();
      if ($entity->getNameTag() === "§7[§c". Implade::getInstance()->getLang("death-nametag") ."§7]§r\n§f" . $player->getName()) Implade::getInstance()->getScheduler()->scheduleDelayedTask(new DespwnParticles(Implade::getInstace(), $entity), 1);
    }
  }
}
