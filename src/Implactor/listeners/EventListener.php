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
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\block\Block;
use pocketmine\nbt\NBT;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\{Player, Server};
use pocketmine\utils\{Config, Utils};
use pocketmine\nbt\tag\{CompoundTag, ListTag, DoubleTag, FloatTag, NamedTag};
use pocketmine\level\sound\{EndermanTeleportSound, GhastSound, BlazeShootSound, AnvilBreakSound};
use pocketmine\event\player\{PlayerPreLoginEvent, PlayerLoginEvent, PlayerJoinEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerRespawnEvent, PlayerChatEvent, PlayerMoveEvent};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};
use pocketmine\level\particle\{DestroyBlockParticle, FlameParticle, HugeExplodeParticle};

use Implactor\Implade;
use Implactor\tasks\{DeathHumanDespawnTask, GuardianJoinTask, TotemRespawnTask};
use Implactor\particles\{DeathParticles, DespawnParticles};
use onebone\economyapi\EconomyAPI;

class EventListener implements Listener {
  
  private $plugin; 
  
  public function __construct(Implade $plugin) {
      $this->plugin = $plugin; 
  }
  
  public function onPreLogin(PlayerPreLoginEvent $ev): void {
    $player = $ev->getPlayer();
    if (!$this->getServer()->isWhitelisted($player->getName())) {
      $ev->setKickMessage($this->plugin->getLang("server-whitelisted-message"));
      $ev->setCancelled(true);
    }
    if ($this->getServer()->getNameBans()->isBanned($player->getName())) {
      $ev->setKickMessage($this->plugin->getLang("player-banned-message"));
      $ev->setCancelled(true);
    }
  }
  
  public function onLogin(PlayerLoginEvent $ev): void {
    $player = $ev->getPlayer();
    $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
    $player->teleport($spawn);
  }
  
  public function onJoin(PlayerJoinEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $player->setGamemode(Player::SURVIVAL);
    if ($this->plugin->getImplade()->get("welcomer-message") == true) {
      if ($player->isOP()) {
        $ev->setJoinMessage($this->plugin->getLang("join-operator-message", array("%player" => $player->getName())));
        $level->addSound(new EndermanTeleportSound($player));
      } else {
        $ev->setJoinMessage($this->plugin->getLang("join-player-message", array("%player" => $player->getName())));
        $level->addSound(new EndermanTeleportSound($player));
      }
    }
    $ev->setJoinMessage(" ");
    if ($this->plugin->getImplade()->get("notice-message") == true) {
      if (!$player->isOP()) {
        $player->sendMessage($this->plugin->impladePrefix . $this->plugin->getLang("join-notice-message"));
      }
    }
    $joinScreen = new GuardianJoinTask($this, $player);
    $this->getScheduler()->scheduleDelayedTask($joinScreen, 25);
    if ($this->plugin->getImplade()->get("lightning-events") == true) {
      $this->plugin->isSummonLightning($player);
    }
    $this->plugin->rainbows[$player->getName()] = 0;
    if (!in_array($player->getName(), $this->plugin->timers)) {
      $this->plugin->timers[] = $player->getName();
    }
    $this->plugin->timers[$player->getName()] = 0;
    $player->getArmorInventory()->clearAll();
  }
  
  public function onDeath(PlayerDeathEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $cause = $player->getLastDamageCause();
    if ($cause instanceof EntityDamageByEntityEvent) {
      $killer = $cause->getDamager();
      if ($killer instanceof Player) {
        $weapon = $killer->getInventory()->getItemInHand()->getName();
        if (!$this->economy->addMoney($killer, $this->plugin->getImplade()->get("killer-money", 220))) {
          $this->getLogger()->error($this->plugin->getLang("economy-error-message"));
          return;
        }
        $player->getServer()->broadcastMessage($this->impladePrefix . $this->getLang("death-money-message", array(
                "%money" => $this->plugin->getImplade()->get("killer-money", 220),
                "%innocent" => $player->getName(),
                "%killer" => $killer->getName(),
                "%weapon" => $weapon
            )));
      }
    }
    $player->sendMessage($this->impladePrefix . $this->plugin->getLang("death-message"));
    if ($this->plugin->getImplade()->get("death-particles") == true) {
      $this->getScheduler()->scheduleDelayedTask(new DeathParticles($this, $player), 1);
    }
    $deathSound = new AnvilBreakSound($player) &&
                  new GhastSound($player);
    $level->addSound($deathSound);
    if ($this->plugin->getImplade()->get("death-human") == true) {
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
      $deathNBT->setTag($player->namedTag->getTag("Skin"));
      $death = new DeathHuman($level, $deathNBT);
      $death->getDataPropertyManager()->setBlockPos(DeathHuman::DATA_PLAYER_BED_POSITION, new Vector3($player->getX(), $player->getY(), $player->getZ()));
      $death->setPlayerFlag(DeathHuman::DATA_PLAYER_FLAG_SLEEP, true);
      $death->setNameTag("§7[". $this->plugin->getLang("death-nametag") ."§7]§r\n§f" . $player->getName());
      $death->setNameTagAlwaysVisible(true);
      $death->spawnToAll();
      $this->getScheduler()->scheduleDelayedTask(new DeathHumanDespawnTask($this, $death, $player) &&
                                                 new DespawnParticles($this, $death, $player), 1100);
    }
  }
  
  public function onRespawn(PlayerRespawnEvent $ev): void {
    $player = $ev->getPlayer();
    $title = $this->plugin->getLang("respawn-title");
    $subtitle = $this->plugin->getLang("respawn-subtitle");
    $player->addTitle($title, $subtitle);
    $player->setGamemode(Player::SURVIVAL);
    $this->getScheduler()->scheduleDelayedTask(new TotemRespawnTask($this, $player), 1);
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
    $iChat = $this->plugin->iChat;
    if (!$player->isOP()) {
    	if (isset($iChat[strtolower($player->getName())])) {
    	    if ((time() - $iChat[strtolower($player->getName())]) < 4) {
    	        $ev->setCancelled();
                $player->sendMessage($this->plugin->getLang("fast-chatting-message"));
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
    if ($this->plugin->getImplade()->get("welcomer-message") == true) {
      if ($player->isOP()) {
        $ev->setQuitMessage($this->plugin->getLang("quit-operator-message", array("%player" => $player->getName())));
        $level->addSound(new BlazeShootSound($player));
      } else {
        $ev->setQuitMessage($this->plugin->getLang("quit-player-message", array("%player" => $player->getName())));
        $level->addSound(new BlazeShootSound($player));
      }
    }
    $ev->setQuitMessage(" ");
    if ($this->plugin->getImplade()->get("lightning-events") == true) {
      $this->plugin->isSummonLightning($player);
    }
  }
  
  public function onDamage(EntityDamageEvent $ev): void {
    $entity = $ev->getEntity();
    $lecek = $entity->getLevel();
    $cause = $ev->getCause();
    if ($entity instanceof Player) {
        if (!$entity->isCreative() && $entity->getAllowFlight()) {
          $entity->setFlying(false);
          $entity->setAllowFlight(false);
          $entity->sendMessage($this->plugin->impladePrefix . $this->plugin->getLang("fly-disabled-damage-message"));
        }
        $wild = $this->plugin->wild;
        if (isset($wild[$entity->getName()])) {
          unset($wild[$entity->getName()]);
          $ev->setCancelled(true);
        }
    }
    $level->addParticle(new DestroyBlockParticle($entity, Block::get(152)));
    if ($entity instanceof SoccerMagma) $ev->setCancelled(true);
    if ($entity instanceof DeathHuman) $ev->setCancelled(true);
    if ($entity instanceof BotHuman) $ev->setCancelled(true);
  }
}
