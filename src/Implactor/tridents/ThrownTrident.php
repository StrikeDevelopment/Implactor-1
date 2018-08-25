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
namespace Implactor\tridents;

use pocketmine\{Player};
use pocketmine\network\mcpe\protocol\{
	PlaySoundPacket as TridentSoundPacket, TakeItemEntityPacket as TridentTakenPacket
};
use pocketmine\entity\Entity;
use pocketmine\block\Block;
use pocketmine\item\Item as TridentItem;
use pocketmine\level\Level;
use pocketmine\entity\projectile\Projectile as TridentProjectile;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\math\RayTraceResult;

class ThrownTrident extends TridentProjectile {

  public const NETWORK_ID = self::TRIDENT;
  public $height = 0.35;
  public $width = 0.25;
  public $gravity = 0.10;
  protected $damage = 7;

  public function __construct(Level $level, CompoundTag $nbt, ?Entity $shootingEntity = \null) {
    parent::__construct($level, $nbt, $shootingEntity);
  }

  public function onCollideWithPlayer(Player $player): void {
    if ($this->blockHit === \null) {
      return;
    }
    $tridentItem = TridentItem::nbtDeserialize($this->namedtag->getCompoundTag(Trident::TRIDENT_SEA_WEAPON));
    $tridentInventory = $player->getInventory();

    if ($player->isSurvival() and !$tridentInventory->canAddItem($tridentItem)) {
      return;
    }
    $packetTaken = new TridentTakenPacket();
    $packetTaken->eid = $player->getId();
    $packetTaken->target = $this->getId();
    $this->server->broadcastPacket($this->getViewers(), $packetTaken);
    if (!$player->isCreative()) {
      $tridentInventory->addItem($tridentItem);
      $tridentItem->setCustomName("§b§lTrident§7");
    }
    $this->flagForDespawn();
  }

  public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {
    if ($entityHit === $this->getOwningEntity()) {
      return;
    }
    parent::onHitEntity($entityHit, $hitResult);
    $packetSound = new TridentSoundPacket();
    $packetSound->x = $this->x;
    $packetSound->y = $this->y;
    $packetSound->z = $this->z;
    $packetSound->soundName = "item.trident.hit";
    $packetSound->volume = 5;
    $packetSound->pitch = 3;
    $this->server->broadcastPacket($this->getViewers(), $packetSound);
  }

  public function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
    parent::onHitBlock($blockHit, $hitResult);
    $packetSound = new TridentSoundPacket();
    $packetSound->x = $this->x;
    $packetSound->y = $this->y;
    $packetSound->z = $this->z;
    $packetSound->soundName = "item.trident.hit_ground";
    $packetSound->volume = 5;
    $packetSound->pitch = 3;
    $this->server->broadcastPacket($this->getViewers(), $packetSound);
  }
}