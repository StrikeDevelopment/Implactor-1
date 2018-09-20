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

use Implactor\Implade;
use Implactor\tasks\ImpladeTask;

class RainbowArmorTask extends ImpladeTask {

  protected $plugin;
  private $player;

  public function __construct(Implade $plugin, Player $player) {
    $this->plugin = $plugin;
    $this->player = $player;
  }

  public function onRun(int $currentTick): void {
    $player = $this->player;
    $plugin = $this->plugin;
    if ($player->isOnline()) {
      $timeColors = Implade::getInstance()->timers[$player->getName()];
      Implade::getInstance()->rainbows[$player->getName()] = $this->getTaskId();
      if ($timeColors === 23) Implade::getInstance()->rainbowArmor($player, 255, 0, 64);
      if ($timeColors === 22) Implade::getInstance()->rainbowArmor($player, 255, 0, 128);
      if ($timeColors === 21) Implade::getInstance()->rainbowArmor($player, 255, 0, 191);
      if ($timeColors === 20) Implade::getInstance()->rainbowArmor($player, 255, 0, 255);
      if ($timeColors === 19) Implade::getInstance()->rainbowArmor($player, 191, 0, 255);
      if ($timeColors === 18) Implade::getInstance()->rainbowArmor($player, 128, 0, 255);
      if ($timeColors === 17) Implade::getInstance()->rainbowArmor($player, 64, 0, 255);
      if ($timeColors === 16) Implade::getInstance()->rainbowArmor($player, 0, 0, 255);
      if ($timeColors === 15) Implade::getInstance()->rainbowArmor($player, 0, 64, 255);
      if ($timeColors === 14) Implade::getInstance()->rainbowArmor($player, 0, 128, 255);
      if ($timeColors === 13) Implade::getInstance()->rainbowArmor($player, 0, 191, 255);
      if ($timeColors === 12) Implade::getInstance()->rainbowArmor($player, 0, 255, 255);
      if ($timeColors === 11) Implade::getInstance()->rainbowArmor($player, 0, 255, 191);
      if ($timeColors === 10) Implade::getInstance()->rainbowArmor($player, 0, 255, 128);
      if ($timeColors === 9) Implade::getInstance()->rainbowArmor($player, 0, 255, 64);
      if ($timeColors === 8) Implade::getInstance()->rainbowArmor($player, 0, 255, 0);
      if ($timeColors === 7) Implade::getInstance()->rainbowArmor($player, 64, 255, 0);
      if ($timeColors === 6) Implade::getInstance()->rainbowArmor($player, 128, 255, 0);
      if ($timeColors === 5) Implade::getInstance()->rainbowArmor($player, 191, 255, 0);
      if ($timeColors === 4) Implade::getInstance()->rainbowArmor($player, 255, 255, 0);
      if ($timeColors === 3) Implade::getInstance()->rainbowArmor($player, 255, 191, 0);
      if ($timeColors === 2) Implade::getInstance()->rainbowArmor($player, 255, 128, 0);
      if ($timeColors === 1) Implade::getInstance()->rainbowArmor($player, 255, 64, 0);
      if ($timeColors === 0) Implade::getInstance()->rainbowArmor($player, 255, 0, 0);
      if ($timeColors === 24) Implade::getInstance()->rainbowArmor($player, 255, 0, 0);
    } else {
      Implade::getInstance()->getScheduler()->cancelTask($this->getTaskId());
    }
  }
}