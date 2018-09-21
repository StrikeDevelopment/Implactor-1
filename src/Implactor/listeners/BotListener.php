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
namespace Implactor\listeners;

use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\{AnimatePacket as SwingPacket, MoveEntityAbsolutePacket as MovementPacket};
use pocketmine\event\entity\{EntitySpawnEvent, EntityDamageEvent, EntityDamageByEntityEvent};
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\Listener;
use Implactor\Implade;
use Implactor\entities\BotHuman;
use Implactor\tasks\BotTask;
use pocketmine\Player;

class BotListener implements Listener {

  private $plugin;

  public function __construct(Implade $plugin) {
    $this->plugin = $plugin;
  }

  public function onEntitySpawn(EntitySpawnEvent $ev): void {
    $entity = $ev->getEntity();
    if ($entity instanceof BotHuman) {
      Implade::getInstance()->getScheduler()->scheduleRepeatingTask(new BotTask(Implade::getInstance(), $entity), 450);
    }
  }

  public function onSwing(EntityDamageEvent $ev): void {
    $entity = $ev->getEntity();
    if ($ev instanceof EntityDamageByEntityEvent) {
      $damager = $ev->getModifier();
      if ($entity instanceof BotHuman && $damager instanceof Player) {
        $packetSwing = new SwingPacket();
        $packetSwing->entityRuntimeId = $entity->getId();
        $packetSwing->action = SwingPacket::ACTION_SWING_ARM;
        $damager->sendDataPacket($packetSwing);
        $damager->sendMessage("§7[". Implade::getInstance()->getLang("bot-nametag") ."§7] " . $this->plugin->getLang("bot-no-hit-damage-message"));
      }
    }
  }

  public function onMove(PlayerMoveEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $from = $ev->getFrom();
    $to = $ev->getTo();
    if ($from->distance($to) < 0.1) return;
    foreach ($level->getNearbyEntities($player->getBoundingBox()->expandedCopy(7, 7, 7), $player) as $bots) {
      if ($bots instanceof BotHuman) {
        $packetMovement = new MovementPacket();
        $vector = new Vector2($bots->x, $bots->z);
        $xRot = ((atan2($player->z - $bots->z, $player->x - $bots->x) * 180) / M_PI) - 90;
        $zRot = ((atan2($vector->distance($player->x, $player->z), $player->y - $bots->y) * 180) / M_PI) - 90;
        $packetMovement->entityRuntimeId = $bots->getId();
        $packetMovement->position = $bots->asVector3()->add(0, 1.5, 0);
        $packetMovement->yaw = $xRot;
        $packetMovement->headYaw = $yRot;
        $packetMovement->pitch = $zRot;
        $player->sendDataPacket($packetMovement);
        $entity->setRotation($xRot, $zRot);
      }
    }
  }
}
