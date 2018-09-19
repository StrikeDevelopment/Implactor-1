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
namespace Implactor;

use pocketmine\Player;
use pocketmine\entity\{Entity, Creature, Human};
use pocketmine\nbt\NBT;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\nbt\tag\{CompoundTag, ListTag, DoubleTag, FloatTag, NamedTag};
use pocketmine\math\Vector3;

use Implactor\Implade;
use Implactor\entities\{BotHuman, DeathHuman, SoccerMagma};
use Implactor\tasks\DeathHumanDespawnTask;
use Implactor\listeners\EventListener;

class EntityManager {

  public $exemptedEntities = [];
  private static $customEntities;
  
  public function __construct() {
    self::$customEntities = $this;
  }
  
  public static function getCustom(): self {
    return self::$customEntities;
  }	
  
  public function spawnSoccer(Player $player): void {
    $level = $player->getLevel();
    $soccerNBT = Entity::createBaseNBT($player, null, 2, 2);
    $soccer = new SoccerMagma($level, $soccerNBT);
    $soccer->setScale(1.4);
    $soccer->spawnToAll();
  }

  public function spawnBot(Player $player, string $botName): void {
    $level = $player->getLevel();
    $botNBT = Entity::createBaseNBT($player, null, 2, 2);
    $botNBT->setTag($player->namedtag->getTag("Skin"));
    $bot = new BotHuman($level, $botNBT);
    $bot->setNameTag("§7[". Implade::getInstance()->getLang("bot-nametag") ."§7]§r\n§f" . $botName);
    $bot->setNameTagAlwaysVisible(true);
    $bot->spawnToAll();
  }
  
  public function spawnDeath(Player $player): void {
    $level = $player->getLevel();
    if (Implade::getInstance()->getImplade()->get("death-human") == true) {
      $deathNBT = new CompoundTag("", [
          new ListTag("Pos", [
              new DoubleTag("", $player->getX()),
              new DoubleTag("", $player->getY() - 1),
              new DoubleTag("", $player->getZ())
          ]),
          new ListTag("Motion", [
              new DoubleTag("", 0),
              new DoubleTag("", 0),
              new DoubleTag("", 0)
          ]),
          new ListTag("Rotation", [
              new FloatTag("", 2),
              new FloatTag("", 2)
          ])
      ]);
      $deathNBT->setTag($player->namedtag->getTag("Skin"));
      $death = new DeathHuman($level, $deathNBT);
      $death->getDataPropertyManager()->setBlockPos(DeathHuman::DATA_PLAYER_BED_POSITION, new Vector3($player->getX(), $player->getY(), $player->getZ()));
      $death->setPlayerFlag(DeathHuman::DATA_PLAYER_FLAG_SLEEP, true);
      $death->setNameTag("§7[". Implade::getInstance()->getLang("death-nametag") ."§7]§r\n§f" . $player->getName());
      $death->setNameTagAlwaysVisible(true);
      $death->spawnToAll();
      self::getCustom()->getScheduler()->scheduleDelayedTask(new DeathHumanDespawnTask(Implade::getInstance(), $death, $player), 1000);
    }
  }
  
  public function isSummonLightning(Player $player, $strike): void {
    if ($strike === true) {
      $level = $player->getLevel();
          
      $thunder = new AddEntityPacket();
      $thunder->type = 93;
      $thunder->entityRuntimeId = Entity::$entityCount++;
      $thunder->position = $player->asVector3()->add(0, 0);
      $thunder->yaw = $player->getYaw();
      $thunder->pitch = $player->getPitch();
      $player->getServer()->broadcastPacket($player->getServer()->getOnlinePlayers(), $thunder);
    }
  }
  
  public function clearCorpses(): int {
    $corpses = 0;
    foreach (Implade::getInstance()->getServer()->getLevels() as $level) {
      foreach ($level->getEntities() as $entity) {
        if ($entity instanceof DeathHuman) {
          $entity->close();
          $corpses++;
        }
      }
    }
    return $corpses;
  }
  
  public function clearItems(): int {
    $item = 0;
    foreach (Implade::getInstance()->getServer()->getLevels() as $level) {
      foreach ($level->getEntities() as $entity) {
        if (!self::getCustom()->isEntityExempted($entity) && !($entity instanceof Creature)) {
          $entity->close();
          $item++;
        }
      }
    }
    return $item;
  }

  public function clearMobs(): int {
    $mobs = 0;
    foreach (Implade::getInstance()->getServer()->getLevels() as $level) {
      foreach ($level->getEntities() as $entity) {
        if (!self::getCustom()->isEntityExempted($entity) && $entity instanceof Creature && !($entity instanceof Human)) {
          $entity->close();
          $mobs++;
        }
      }
    }
    return $mobs;
  }

  public function exemptEntity(Entity $entity): void {
    self::getCustom()->exemptedEntities[$entity->getID()] = $entity;
  }

  public function isEntityExempted(Entity $entity): bool {
    return isset(self;:getCustom()->exemptedEntities[$entity->getID()]);
  }
}
