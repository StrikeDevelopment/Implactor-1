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
namespace Implactor\particles;

use Implactor\entities\DeathHuman;
use pocketmine\level\particle\{HugeExplodeParticle as BigExplosion, LavaParticle as LavaExplosion, RedstoneParticle as RedExplosion};
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use Implactor\Implade;
use Implactor\tasks\ImpladeTask;

class DespawnParticles extends ImpladeTask {

  private $plugin;
  private $entity;

  public function __construct(Implade $plugin, Entity $entity) {
    $this->plugin = $plugin;
    $this->entity = $entity;
  }

  public function onRun(int $currentTick): void {
    $entity = $this->entity;
    if ($entity instanceof DeathHuman) {
      $despawn = $entity->getLevel();
      $x = $entity->getX();
      $y = $entity->getY();
      $z = $entity->getZ();
      $center = new Vector3($x, $y, $z);
      $despawnExplosion = new BigExplosion($center);
      $despawnLava = new LavaExplosion($center);
      $despawnRed = new RedExplosion($center);
      for ($yaw = 0, $y = $center->y; $y < $center->y + 4; $yaw += (M_PI * 2) / 20, $y += 1 / 20) {
        $x = -sin($yaw) + $center->x;
        $z = cos($yaw) + $center->z;
        $despawnExplosion->setComponents($x, $y, $z);
        $despawnLava->setComponents($x, $y, $z);
        $despawnRed->setComponents($x, $y, $z);
        $despawn->addParticle($despawnExplosion);
        $despawn->addParticle($despawnLava);
        $despawn->addParticle($despawnRed);
      }
    }
  }
}
