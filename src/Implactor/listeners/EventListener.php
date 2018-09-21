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

use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\{Player, Server};
use pocketmine\utils\{Config, Utils};
use pocketmine\level\sound\{EndermanTeleportSound, GhastSound, BlazeShootSound, AnvilBreakSound};
use pocketmine\event\player\{PlayerPreLoginEvent, PlayerLoginEvent, PlayerJoinEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerRespawnEvent, PlayerChatEvent, PlayerMoveEvent};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};
use pocketmine\level\particle\{DestroyBlockParticle, FlameParticle, HugeExplodeParticle};
use pocketmine\math\Vector3;

use Implactor\{Implade, EntityManager};
use Implactor\tasks\{GuardianJoinTask, TotemRespawnTask};
use Implactor\particles\DeathParticles;
use onebone\economyapi\EconomyAPI;

class EventListener implements Listener {
  
  private $plugin; 
  
  private $iChat = [];
  private $wild = [];
  
  public function __construct(Implade $plugin) {
    $this->plugin = $plugin; 
  }
  
  public function onPreLogin(PlayerPreLoginEvent $ev): void {
    $player = $ev->getPlayer();
    if (!Implade::getInstance()->getServer()->isWhitelisted($player->getName())) {
      $ev->setKickMessage(Implade::getInstance()->getLang("server-whitelisted-message"));
      $ev->setCancelled(true);
    }
    if (Implade::getInstance()->getServer()->getNameBans()->isBanned($player->getName())) {
      $ev->setKickMessage(Implade::getInstance()->getLang("player-banned-message"));
      $ev->setCancelled(true);
    }
  }
  
  public function onLogin(PlayerLoginEvent $ev): void {
    $player = $ev->getPlayer();
    $spawn = Implade::getInstance()->getServer()->getDefaultLevel()->getSafeSpawn();
    $player->teleport($spawn);
  }
  
  public function onJoin(PlayerJoinEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $player->setGamemode(Player::SURVIVAL);
    if (Implade::getInstance()->getImplade()->get("welcomer-message") == true) {
      if ($player->isOP()) {
        if (Implade::getInstance()->getImplade()->get("notice-message") == true) {
          $player->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("join-notice-message"));
        }
        $ev->setJoinMessage(Implade::getInstance()->getLang("join-operator-message", array(
                "%player" => $player->getName()
            )));
        $level->addSound(new EndermanTeleportSound($player));
      } else {
        $ev->setJoinMessage(Implade::getInstance()->getLang("join-player-message", array(
                "%player" => $player->getName()
            )));
        $level->addSound(new EndermanTeleportSound($player));
      }
    }
    Implade::getInstance()->getScheduler()->scheduleDelayedTask(new GuardianJoinTask(Implade::getInstance(), $player), 25);
    if (Implade::getInstance()->getImplade()->get("lightning-events") == true) {
      Implade::getInstance()->isSummonLightning($player, true); 
    }
    Implade::getInstance()->rainbows[$player->getName()] = 0;
    if (!in_array($player->getName(), Implade::getInstance()->timers)) {
      Implade::getInstance()->timers[] = $player->getName();
    }
    Implade::getInstance()->timers[$player->getName()] = 0;
    $player->getArmorInventory()->clearAll();
  }
  
  public function onDeath(PlayerDeathEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $cause = $player->getLastDamageCause();
    if ($cause instanceof EntityDamageByEntityEvent) {
      $killer = $cause->getModifier();
      if ($killer instanceof Player) {
        $weapon = $killer->getInventory()->getItemInHand()->getName();
        if (!Implade::getInstance()->economy->addMoney($killer, Implade::getInstance()->getImplade()->get("killer-money", 220))) {
          $this->getLogger()->error(Implade::getInstance()->getLang("economy-error-message"));
          return;
        }
        $player->getServer()->broadcastMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("death-money-message", array(
                "%money" => Implade::getInstance()->getImplade()->get("killer-money", 220),
                "%innocent" => $player->getName(),
                "%killer" => $killer->getName(),
                "%weapon" => $weapon
            )));
      }
    }
    $player->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("death-message"));
    if (Implade::getInstance()->getImplade()->get("death-particles") == true) {
      Implade::getInstance()->getScheduler()->scheduleDelayedTask(new DeathParticles(Implade::getInstance(), $player), 1);
    }
    $deathSound = new AnvilBreakSound($player);
    $deathSound = new GhastSound($player);
    $level->addSound($deathSound);
    EntityManager::getCustom()->spawnDeath($player);
  }
  
  public function onRespawn(PlayerRespawnEvent $ev): void {
    $player = $ev->getPlayer();
    $title = Implade::getInstance()->getLang("respawn-title");
    $subtitle = Implade::getInstance()->getLang("respawn-subtitle");
    $player->addTitle($title, $subtitle);
    $player->setGamemode(Player::SURVIVAL);
    Implade::getInstance()->getScheduler()->scheduleDelayedTask(new TotemRespawnTask(Implade::getInstance(), $player), 1);
  }
  
  public function onMove(PlayerMoveEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $from = $ev->getFrom();
    $to = $ev->getTo();
    $speed = $from->distanceSquared($to) * 5;
    foreach ($level->getNearByEntities($player->getBoundingBox()->expandedCopy(0.6, 0.6, 0.6), $player) as $entity) {
      if ($entity instanceof SoccerMagma) {
        $level = $entity->getLevel();
        $level->addParticle(new FlameParticle($entity));
        switch ($player->getDirection()) {
          case 0:
            $entity->setMotion(new Vector3($speed, $speed / 4, 0));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $level->addParticle(new HugeExplodeParticle($entity));
            break;
          case 1:
            $entity->setMotion(new Vector3(0, $speed / 4, $speed));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $level->addParticle(new HugeExplodeParticle($entity));
            break;
          case 2:
            $entity->setMotion(new Vector3(-$speed, $speed / 4, 0));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $level->addParticle(new HugeExplodeParticle($entity));
            break;
          case 3:
            $entity->setMotion(new Vector3(0, $speed / 4, -$speed));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $level->addParticle(new HugeExplodeParticle($entity));
            break;
        }
      }
    }
  }
  
  public function onChat(PlayerChatEvent $ev): void {
    $player = $ev->getPlayer();
    $iChat = $this->iChat;
    if (!$player->isOP()) {
    	if (isset($iChat[strtolower($player->getName())])) {
    	    if ((time() - $iChat[strtolower($player->getName())]) < 5) {
    	        $ev->setCancelled();
              $player->sendMessage(Implade::getInstance()->getLang("fast-chatting-message"));
            } else {
            	$iChat[strtolower($player->getName())] = time();
            }
       } else {
       	$iChat[strtolower($player->getName())] = time();
       }
     }
  }
  
  public function onQuit(PlayerQuitEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $player->setGamemode(Player::SURVIVAL);
    if (Implade::getInstance()->getImplade()->get("welcomer-message") == true) {
      if ($player->isOP()) {
        $ev->setQuitMessage(Implade::getInstance()->getLang("quit-operator-message", array("%player" => $player->getName())));
        $level->addSound(new BlazeShootSound($player));
      } else {
        $ev->setQuitMessage(Implade::getInstance()->getLang("quit-player-message", array("%player" => $player->getName())));
        $level->addSound(new BlazeShootSound($player));
      }
    }
    if (Implade::getInstance()->getImplade()->get("lightning-events") == true) {
      Implade::getInstance()->isSummonLightning($player, true); 
    }
  }
  
  public function onDamage(EntityDamageEvent $ev): void {
    $entity = $ev->getEntity();
    $level = $entity->getLevel();
    $cause = $ev->getCause();
    if ($entity instanceof Player) {
        if (!$entity->isCreative() && $entity->getAllowFlight()) {
          $entity->setFlying(false);
          $entity->setAllowFlight(false);
          $entity->sendMessage($this->plugin->impladePrefix . $this->plugin->getLang("fly-disabled-damage-message"));
        }
        $wilder = $this->wild;
        if (isset($wilder[$entity->getName()])) {
          unset($wilder[$entity->getName()]);
          $ev->setCancelled(true);
        }
    }
    if (Implade::getInstance()->getImplade()->get("blood-hit-players") == false) {
      $level->addParticle(new DestroyBlockParticle($entity, Block::get(152)));
    }
    if ($entity instanceof SoccerMagma) $ev->setCancelled(true);
    if ($entity instanceof DeathHuman) $ev->setCancelled(true);
    if ($entity instanceof BotHuman) $ev->setCancelled(true);
  }
}
