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

use pocketmine\level\particle\{HappyVillagerParticle as Experience, PortalParticle as Portal, WaterParticle as Water};
use pocketmine\math\Vector3;

use Implactor\Implade;
use Implactor\tasks\ImpladeTask;

class SpawnParticles extends ImpladeTask {
	
	protected $plugin;
	
	public function __construct(Implade $plugin){
		$this->plugin = $plugin;
	}
	
	public function onRun(int $currentTick): void{
		$alive = $this->plugin->getServer()->getDefaultLevel();
		$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
		$x = $spawn->getX();
		$y = $spawn->getY();
		$z = $spawn->getZ();
		$center = new Vector3($x, $y, $z);
		$expSpawn = new Experience($center);
		$portalSpawn = new Portal($center);
		$waterSpawn = new Water($center);
		
		for($yaw = 0, $y = $center->y; $y < $center->y + 4; $yaw += (M_PI * 2) / 20, $y += 1 / 20){
			$x = -sin($yaw) + $center->x;
			$z = cos($yaw) + $center->z;
			$expSpawn->setComponents($x, $y, $z);
			$portalSpawn->setComponents($x, $y, $z);
			$waterSpawn->setComponents($x, $y, $z);
			$alive->addParticle($expSpawn);
			$alive->addParticle($portalSpawn);
			$alive->addParticle($waterSpawn);
		}
	}
}
