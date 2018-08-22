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

use pocketmine\entity\Creature;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\item\Armor;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Color;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;
use pocketmine\command\{Command, CommandSender};
use pocketmine\plugin\{Plugin, PluginBase, PluginDescription};
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};
use pocketmine\level\particle\{DestroyBlockParticle, FlameParticle, HugeExplodeParticle};
use pocketmine\nbt\tag\{CompoundTag, ListTag, DoubleTag, FloatTag, NamedTag, StringTag};
use pocketmine\level\sound\{EndermanTeleportSound, BlazeShootSound, AnvilBreakSound, DoorBumpSound};
use pocketmine\event\player\{PlayerPreLoginEvent, PlayerLoginEvent, PlayerJoinEvent, PlayerQuitEvent, PlayerDeathEvent, PlayerRespawnEvent, PlayerChatEvent, PlayerMoveEvent};
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\event\Listener;
use pocketmine\inventory\PlayerInventory;
use Implactor\entities\{BotHuman, DeathHuman, SoccerMagma};
use Implactor\listeners\{AntiAdvertising, AntiCaps, AntiSwearing, BotListener};
use Implactor\tasks\{ChatCooldownTask, ClearLaggTask, DeathHumanDespawnTask, GuardianJoinTask, TotemRespawnTask, RainbowArmorTask};
use Implactor\particles\{DeathParticles, DespawnParticles, SpawnParticles};
use Implactor\tridents\{TridentEntityManager, TridentItemManager};
use onebone\economyapi\EconomyAPI;
use jojoe77777\FormAPI\FormAPI;

class Implade extends PluginBase implements Listener {

  const VERSION = 1;

  protected $lang;
  protected $forms;
  protected $economy;

  public $impladePrefix = "§7[§aI§6R§7]§r ";
  public $rainbows = array();
  public $timers = array();
  public $ichat = [];
  public $config;

  private $wild = [];
  private $visible = [];
  public $exemptedEntities = [];

  public function getConfig(): Config {
    return $this->config;
  }

  public function onLoad(): void {
    if (!is_dir($this->getDataFolder())) {
      mkdir($this->getDataFolder());
    }
    if (!is_file($this->getDataFolder() . "iConfig.yml")) {
      $this->saveResource("iConfig.yml");
    }
    $this->config = new Config($this->getDataFolder() . "iConfig.yml");
    $this->configLanguages();
    if ($this->getConfig()->get("update-checker", true)) {
      $this->getLogger()->notice($this->getLang("update-checking-message"));
      try {
        if (($version = (new PluginDescription(file_get_contents("https://raw.githubusercontent.com/ImpladeDeveloped/Implactor/Implade/plugin.yml")))->getVersion()) != $this->getDescription()->getVersion()) {
          $this->getLogger()->notice($this->getLang("update-detected-message", array("%version" => $version)));
        } else {
          $this->getLogger()->info($this->getLang("update-already-message"));
        }
      } catch (\Exception $ex) {
        $this->getLogger()->warning($this->getLang("update-unable-message"));
      }
    }
    if ($this->getConfig()->get('Version') < self::VERSION) {
      rename($this->getDataFolder() . "iConfig.yml", $this->getDataFolder() . "[Outdated] iConfig.yml");
      $this->getConfig()->reload();
      $this->getLogger()->notice($this->getLang("outdated-config-message"));
    }
  }

  public function onEnable(): void {
    $this->config = new Config($this->getDataFolder() . "iConfig.yml");
    $this->checkDepends();
    $this->checkEntities();
    $this->checkTridents();
    $this->getServer()->getPluginManager()->registerEvents($this, $this);
    $this->getServer()->getPluginManager()->registerEvents(new AntiAdvertising($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new AntiCaps($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new AntiSwearing($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new BotListener($this), $this);
    $this->getLogger()->info($this->getLang("license-plugin-message"));
    if (is_numeric($this->getConfig()->get("clear-timer"))) {
      $this->getScheduler()->scheduleRepeatingTask(new ClearLaggTask($this), $this->getConfig()->get("clear-timer") * 20);
    } else {
      $this->getLogger()->error($this->getLang("clearlagg-error-message"));
    }
    if ($this->getConfig()->get("spawn-particles") == true) {
      $this->getScheduler()->scheduleRepeatingTask(new SpawnParticles($this), 15);
    }
    $this->getLogger()->info($this->getLang("enable-plugin-message"));
  }

  public function checkDepends(): void {
    $this->forms = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    if (is_null($this->forms)) {
      $this->getLogger()->warning($this->getLang("forms-failed-message"));
    } else {
      $this->getLogger()->debug($this->getLang("forms-found-message"));
    }
    $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
    if (is_null($this->economy)) {
      $this->getLogger()->warning($this->getLang("economy-failed-message"));
    } else {
      $this->getLogger()->debug($this->getLang("economy-found-message"));
    }
  }

  public function checkEntities(): void {
    $this->getLogger()->debug($this->getLang("check-entities-message"));
    Entity::registerEntity(DeathHuman::class, true);
    Entity::registerEntity(BotHuman::class, true);
    Entity::registerEntity(SoccerMagma::class, true);
  }

  private function checkTridents() {
    $this->getLogger()->debug($this->getLang("check-tridents-message"));
    TridentEntityManager::init();
    TridentItemManager::init();
  }

  public function configLanguages(): void {
    if (!file_exists($this->getDataFolder() . "languages/")) {
      @mkdir($this->getDataFolder() . "languages/");
    }
    $language = $this->getConfig()->get('language');
    if (!is_file($this->getDataFolder() . "languages/{$language}.yml")) {
      if ($this->saveResource("languages/{$language}.yml")) {
        $this->getLogger()->warning("{$language} is not found in our Implactor languages database, switched to default English language!");
        $language = 'English';
        $this->saveResource("languages/English.yml");
      }
    }
    $this->lang = new Config($this->getDataFolder() . "languages/{$language}.yml", Config::YAML);
    $this->lang->save();
    $this->getLogger()->info($this->getLang("language-selected"));
  }

  public function onDisable(): void {
    $this->getLogger()->info($this->getLang("disable-plugin-message"));
  }

  public function onPreLogin(PlayerPreLoginEvent $ev): void {
    $player = $ev->getPlayer();
    if (!$this->getServer()->isWhitelisted($player->getName())) {
      $ev->setKickMessage($this->getLang("server-whitelisted-message"));
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
    if ($player->isOP()) {
      $ev->setJoinMessage($this->getLang("join-operator-message", array("%player" => $player->getName())));
    } else {
      $ev->setJoinMessage($this->getLang("join-player-message", array("%player" => $player->getName())));
    }
    $level->addSound(new EndermanTeleportSound($player));
    $joinScreen = new GuardianJoinTask($this, $player);
    $this->getScheduler()->scheduleDelayedTask($joinScreen, 25);
    $player->sendMessage($this->impladePrefix . $this->getLang("join-notice-message"));
    $this->rainbows[$player->getName()] = 0;
    if (!in_array($player->getName(), $this->timers)) {
      $this->timers[] = $player->getName();
    }
    $this->timers[$player->getName()] = 0;
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
        if (!$this->economy->addMoney($killer, $this->getConfig()->get("killer-money", 220))) {
          $this->getLogger()->error($this->getLang("economy-error-message"));
          return;
        }
        $player->getServer()->broadcastMessage($this->impladePrefix . $this->getLang("death-money-message", array(
                "%money" => $this->getConfig()->get("killer-money", 220),
                "%innocent" => $player->getName(),
                "%killer" => $killer->getName(),
                "%weapon" => $weapon
            )));
      }
    }
    $player->sendMessage($this->impladePrefix . $this->getLang("death-message"));
    $deathSound = new AnvilBreakSound($player);
    $level->addSound($deathSound);
    if ($this->getConfig()->get("death-and-despawn-particles") == true) {
      $this->getScheduler()->scheduleDelayedTask(new DeathParticles($this, $player), 1);
      $this->getScheduler()->scheduleDelayedTask(new DespawnParticles($this, $player), 1300);
    }
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
    $death->setNameTag("§7[". $this->getLang("death-nametag") ."§7]§r\n§f" . $player->getName());
    $death->setNameTagAlwaysVisible(true);
    $death->spawnToAll();
    $this->getScheduler()->scheduleDelayedTask(new DeathHumanDespawnTask($this, $death, $player), 1300);
  }

  public function onRespawn(PlayerRespawnEvent $ev): void {
    $player = $ev->getPlayer();
    $title = $this->getLang("respawn-title");
    $subtitle = $this->getLang("respawn-subtitle");
    $player->addTitle($title, $subtitle);
    $player->setGamemode(Player::SURVIVAL);
    $this->getScheduler()->scheduleDelayedTask(new TotemRespawnTask($this, $player), 1);
  }

  public function onMove(PlayerMoveEvent $ev): void {
    $player = $ev->getPlayer();
    $speed = $ev->getFrom()->distanceSquared($ev->getTo()) * 5;
    foreach ($player->getLevel()->getNearByEntities($player->getBoundingBox()->expandedCopy(0.6, 0.6, 0.6), $player) as $entity) {
      if ($entity instanceof SoccerMagma) {
        $entity->getLevel()->addParticle(new FlameParticle($entity));
        switch ($player->getDirection()) {
          case 0:
            $entity->setMotion(new Vector3($speed, $speed / 4, 0));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $entity->getLevel()->addParticle(new HugeExplodeParticle($entity));
            break;

          case 1:
            $entity->setMotion(new Vector3(0, $speed / 4, $speed));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $entity->getLevel()->addParticle(new HugeExplodeParticle($entity));
            break;

          case 2:
            $entity->setMotion(new Vector3(-$speed, $speed / 4, 0));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $entity->getLevel()->addParticle(new HugeExplodeParticle($entity));
            break;

          case 3:
            $entity->setMotion(new Vector3(0, $speed / 4, -$speed));
            $entity->level->broadcastLevelSoundEvent($entity, LevelSoundEventPacket::SOUND_POP);
            $entity->getLevel()->addParticle(new HugeExplodeParticle($entity));
            break;
        }
      }
    }
  }

  public function onChat(PlayerChatEvent $ev): void {
    $player = $ev->getPlayer();
    if (isset($this->ichat[$player->getName()])) {
      $ev->setCancelled(true);
      $player->sendMessage($this->getLang("fast-chatting-message"));
    }
    if (!$player->hasPermission("implactor.chatcooldown")) {
      $this->ichat[$player->getName()] = true;
      $this->getScheduler()->scheduleDelayedTask(new ChatCooldownTask($this, $player), 200);
    }
  }

  public function onQuit(PlayerQuitEvent $ev): void {
    $player = $ev->getPlayer();
    $level = $player->getLevel();
    $player->setGamemode(Player::SURVIVAL);
    if ($player->isOP()) {
      $ev->setQuitMessage($this->getLang("quit-operator-message", array("%player" => $player->getName())));
      $quitSound = new BlazeShootSound($player);
      $level->addSound($quitSound);
    } else {
      $ev->setQuitMessage($this->getLang("quit-player-message", array("%player" => $player->getName())));
      $quitSound = new BlazeShootSound($player);
      $level->addSound($quitSound);
    }
  }

  public function onDamage(EntityDamageEvent $ev): void {
    $entity = $ev->getEntity();
    $cause = $ev->getCause();
    if ($entity instanceof Player) {
      if ($cause !== $ev::CAUSE_FALL) {
        if ($entity->isCreative()) return;
        if ($entity->getAllowFlight() == true) {
          $entity->setFlying(false);
          $entity->setAllowFlight(false);
          $entity->sendMessage($this->impladePrefix . $this->getLang("fly-disabled-damage-message"));
        }
        if (isset($this->wild[$entity->getName()])) {
          unset($this->wild[$entity->getName()]);
          $ev->setCancelled(true);
        }
      }
    }
    $entity->getLevel()->addParticle(new DestroyBlockParticle($entity, Block::get(169)));
    if ($entity instanceof SoccerMagma) $ev->setCancelled(true);
    if ($entity instanceof DeathHuman) $ev->setCancelled(true);
    if ($entity instanceof BotHuman) $ev->setCancelled(true);
  }

  public function spawnSoccer(Player $player): void {
    $level = $player->getLevel();
    $soccerNBT = Entity::createBaseNBT($player, null, 2, 2);
    $soccer = new SoccerMagma($level, $soccerNBT);
    $soccer->setScale(1.6);
    $soccer->spawnToAll();
  }

  public function spawnBot(Player $player, string $botName): void {
    $level = $player->getLevel();
    $botNBT = Entity::createBaseNBT($player, null, 2, 2);
    $botNBT->setTag($player->namedtag->getTag("Skin"));
    $bot = new BotHuman($level, $botNBT);
    $bot->setNameTag("§7[". $this->getLang("bot-nametag") ."§7]§r\n§f" . $botName);
    $bot->setNameTagAlwaysVisible(true);
    $bot->spawnToAll();
  }

  public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
    if (!$sender instanceof Player) {
      $sender->sendMessage($this->getLang("only-command-ingame-message"));
      return true;
    }
    if (strtolower($command->getName()) === "bot") {
      if ($sender->hasPermission("implactor.bot")) {
        $this->botMenu($sender);
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "soccer") {
      if ($sender->hasPermission("implactor.soccer")) {
        $this->spawnSoccer($sender);
        $sender->level->broadcastLevelSoundEvent($sender, LevelSoundEventPacket::SOUND_POP);
        $sender->sendMessage($this->impladePrefix . $this->getLang("soccer-spawned-message"));
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } elseif (strtolower($command->getName()) === "iabout") {
      if ($sender->hasPermission("implactor.about")) {
        $sender->sendMessage("§7-----×>>");
        $sender->sendMessage("§7- §6Impl§5actor");
        $sender->sendMessage($this->getLang("author-message") . " §fZadezter");
        $sender->sendMessage($this->getLang("created-message") . " §f23 May 2018");
        $sender->sendMessage("§7-----×>>");
      } else {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "rainbow") {
      if ($sender->hasPermission("implactor.rainbow")) {
        $this->rainbowMenu($sender);
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "visible") {
      if ($sender->hasPermission("implactor.visible")) {
        $this->visibleMenu($sender);
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "vision") {
      if ($sender->hasPermission("implactor.vision")) {
        $this->visionMenu($sender);
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "ping") {
      if ($sender->hasPermission("implactor.ping")) {
        $sender->sendMessage($sender->getPlayer()->getName() . $this->getLang("ping-status-message", array("%ping" => $sender->getPing())));
      } else {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "wild") {
      if ($sender->hasPermission("implactor.wild")) {
        $x = rand(1, 999);
        $y = 128;
        $z = rand(1, 999);
        $wilder = $sender->getLevel()->getSafeSpawn(new Vector3($x, $y, $z));
        $sender->teleport($wilder);
        $this->wild[$sender->getName()] = true;

        $title = $this->getLang("wild-title");
        $subtitle = $this->getLang("wild-subtitle");
        $sender->addTitle($title, $subtitle);
      } else {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "icast") {
      if ($sender->hasPermission("implactor.broadcast")) {
        if (count($args) < 1) {
          $sender->sendMessage($this->getLang("command-usage-message"). "§e/icast <" .$this->getLang("broadcast-usage-argument-message"). "§e>");
          return false;     
        }   
          $sender->getServer()->broadcastMessage("§7[§bImplacast§7] §e" . implode(" ", $args));
        } else {
          $sender->sendMessage($this->impladePrefix . $this->getLang("no-permission-message"));
          return false;
      }
    } else if (strtolower($command->getName()) === "gms" || strtolower($command->getName()) === "gmc" || strtolower($command->getName()) === "gma" || strtolower($command->getName()) === "gmsc") {
      if (!$sender->hasPermission("implactor.gamemode")) {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-permission-message"));
        return false;
      }
      $gamemode = [Player::SURVIVAL, "survival"];
      if (strtolower($command->getName()) === "gmc") {
        $gamemode = [Player::CREATIVE, "creative"];
      } else if (strtolower($command->getName()) === "gma") {
        $gamemode = [Player::ADVENTURE, "adventure"];
      } else if (strtolower($command->getName()) === "gmsc") {
        $gamemode = [Player::SPECTATOR, "spectator"];
      }
      if (empty($args[0])) {
        $sender->setGamemode($gamemode[0]);
        $sender->sendMessage($this->impladePrefix . $this->getLang("{$gamemode[1]}-set-yourself-message"));
        return false;
      }
      $player = $this->getServer()->getPlayer($args[0]);
      if ($player) {
        $player->setGamemode($gamemode[0]);
        $sender->sendMessage($this->impladePrefix . $this->getLang("{$gamemode[1]}-set-player-message", array("%player" => $player->getName())));
        $player->sendMessage($this->impladePrefix . $this->getLang("{$gamemode[1]}-noticed-player-message", array("%player" => $sender->getName())));
      } else {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-player-found-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "clearinv") {
      if (!$sender->hasPermission("implactor.inventory")) {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-permission-message"));
        return false;
      }
      if (empty($args[0])) {
        $sender->getInventory()->clearAll();
        $sender->sendMessage($this->impladePrefix . $this->getLang("clear-inventory-set-yourself-message"));
        return false;
      }
      $player = $this->getServer()->getPlayer($args[0]);
      if ($player) {
        $player->getInventory()->clearAll();
        $sender->sendMessage($this->impladePrefix . $this->getLang("clear-inventory-set-player-message", array("%player" => $player->getName())));
        $player->sendMessage($this->impladePrefix . $this->getLang("clear-inventory-noticed-player-message", array("%player" => $sender->getName())));
      } else {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-player-found-message"));
        return false;
      }
      return true;
    } else if (strtolower($command->getName()) === "cleararmor") {
      if (!$sender->hasPermission("implactor.inventory")) {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-permission-message"));
        return false;
      }
      if (empty($args[0])) {
        $sender->getArmorInventory()->clearAll();
        $sender->sendMessage($this->impladePrefix . $this->getLang("clear-armor-set-yourself-message"));
        return false;
      }
      $player = $this->getServer()->getPlayer($args[0]);
      if ($this->getServer()->getPlayer($args[0])) {
        $player->getArmorInventory()->clearAll();
        $sender->sendMessage($this->impladePrefix . $this->getLang("clear-armor-set-player-message"));
        $player->sendMessage($this->impladePrefix . $sender->getName() . $this->getLang("clear-armor-noticed-player-message"));
      } else {
        $sender->sendMessage($this->impladePrefix . $this->getLang("no-player-found-message"));
        return false;
      }
    }
    return true;
  }

  public function getLang(string $configKey, array $keys = array()) {
    $language = $this->lang;
    $key = $language->get($configKey);
    if (!is_string($key))
      return "There is an error with message key, ({$configKey}). Please contact with the author or collabrators!!";
    $key = strtr($key, $keys);
    return str_replace("&", "§", $key);
  }

  public function visionMenu($sender): void {
    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $sender->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 1000000, 254, true));
          $sender->sendMessage($this->impladePrefix . $this->getLang("vision-enabled-message"));
          break;
        case 1:
          $sender->removeEffect(Effect::NIGHT_VISION);
          $sender->sendMessage($this->impladePrefix . $this->getLang("vision-disabled-message"));
          break;
        case 2:
          $sender->sendMessage($this->impladePrefix . $this->getLang("close-form-message"));
          break;
      }
    });
    $form->setTitle($this->getLang("form-menu-title"));
    $form->setContent($this->getLang("vision-content-message"));
    $form->addButton($this->getLang("enable-message"), 1, "https://cdn.discordapp.com/attachments/442624759985864714/468316317351542804/On.png");
    $form->addButton($this->getLang("disable-message"), 2, "https://cdn.discordapp.com/attachments/442624759985864714/468316317351542806/Off.png");
    $form->addButton($this->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $form->sendToPlayer($sender);
  }

  public function visibleMenu($sender): void {
    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $sender->addTitle($this->getLang("visible-on-message"), $this->getLang("visible-show-message"));
          unset($this->visible[array_search($sender->getName(), $this->visible)]);
          foreach ($this->getServer()->getOnlinePlayers() as $visibler) {
            $sender->showplayer($visibler);
          }
          break;
        case 1:
          $sender->addTitle($this->getLang("visible-off-message"), $this->getLang("visible-hide-message"));
          $this->visible[] = $sender->getName();
          foreach ($this->getServer()->getOnlinePlayers() as $visibler) {
            $sender->hideplayer($visibler);
          }
          break;
        case 2:
          $sender->sendMessage($this->impladePrefix . $this->getLang("close-form-message"));
          break;
      }
    });
    $form->setTitle($this->getLang("form-menu-title"));
    $form->setContent($this->getLang("visible-content-message"));
    $form->addButton($this->getLang("show-message"), 1, "https://cdn.discordapp.com/attachments/442624759985864714/468316318060249098/Show.png");
    $form->addButton($this->getLang("hide-message"), 2, "https://cdn.discordapp.com/attachments/442624759985864714/468316318060249099/Hide.png");
    $form->addButton($this->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $form->sendToPlayer($sender);
  }

  public function botMenu($sender): void {
    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $this->spawnBotForm($sender);
          break;
        case 1:
          $this->clearBotForm($sender);
          break;
        case 2:
          $sender->sendMessage($this->impladePrefix . $this->getLang("close-form-message"));
          break;
      }
    });
    $form->setTitle($this->getLang("form-menu-title"));
    $form->setContent($this->getLang("bot-content-message"));
    $form->addButton($this->getLang("bot-spawn-button-message"), 1, "");
    $form->addButton($this->getLang("bot-clear-button-message"), 2, "");
    $form->addButton($this->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $form->sendToPlayer($sender);
  }

  public function spawnBotForm($sender): void {
    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createCustomForm(function (Player $sender, $result) {
      if ($result !== null) {
        $this->spawnBot($sender, $result[1]);
        $sender->getServer()->broadcastMessage("§7[§bBot§7]§f " . $this->getLang("bot-spawned-message", array("%bot" => $result[1], "%player" => $sender->getName())));
        $sender->getLevel()->addSound(new DoorBumpSound($sender));
      }
    });
    $form->setTitle($this->getLang("form-menu-title"));
    $form->addLabel($this->getLang("bot-label-message"));
    $form->addInput($this->getLang("bot-input"), $this->getLang("bot-input-name"));
    $form->sendToPlayer($sender);
  }

  public function clearBotForm($sender): void {
    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $clearBots = 0;
          foreach ($this->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
              if ($entity instanceof BotHuman) {
                $entity->close();
                $clearBots++;
              }
            }
          }
          $sender->sendMessage($this->impladePrefix . $this->getLang("bot-success-cleared-message", array("bots" => $clearBots)));
          break;
        case 1:
          $this->botMenu($sender);
          break;
      }
    });
    $form->setTitle($this->getLang("form-procced-title"));
    $form->setContent($this->getLang("bot-clear-content-message"));
    $form->addButton($this->getLang("yes-message"), 1, "");
    $form->addButton($this->getLang("no-message"), 2, "");
    $form->sendToPlayer($sender);
  }

  public function rainbowMenu($sender): void {
    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          if ($this->rainbows[$sender->getName()] === 0) {
            $rainbowStartTask = new RainbowArmorTask($this, $sender);
            $this->getScheduler()->scheduleRepeatingTask($rainbowStartTask, 5);
            $sender->sendMessage($this->impladePrefix . $this->getLang("rainbow-enabled-message"));
          }
          break;
        case 1:
          $this->disableRainbowForm($sender);
          break;
        case 2:
          $sender->sendMessage($this->impladePrefix . $this->getLang("close-form-message"));
          break;
      }
    });
    $form->setTitle($this->getLang("form-menu-title"));
    $form->setContent($this->getLang("rainbow-content-message"));
    $form->addButton($this->getLang("enable-message"), 1, "");
    $form->addButton($this->getLang("disable-message"), 2, "");
    $form->addButton($this->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $form->sendToPlayer($sender);
  }

  public function disableRainbowForm($sender): void {
    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    $form = $api->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          if ($this->rainbows[$sender->getName()] === 0) {
            $rainbowCancelTask = $this->rainbows[$sender->getName()];
            $this->getScheduler()->cancelTask($rainbowCancelTask);
            $this->rainbows[$sender->getName()] = 0;
            $sender->getArmorInventory()->clearAll();
            $sender->sendMessage($this->impladePrefix . $this->getLang("rainbow-disabled-message"));
          }
          break;
        case 1:
          $this->rainbowMenu($sender);
          break;
      }
    });
    $form->setTitle($this->getLang("form-procced-title"));
    $form->setContent($this->getLang("rainbow-disable-content-message"));
    $form->addButton($this->getLang("yes-message"), 1, "");
    $form->addButton($this->getLang("no-message"), 2, "");
    $form->sendToPlayer($sender);
  }

  public function rainbowArmor(Player $player, int $r, int $b, int $g): void {
    $gear = new Color($r, $b, $g);
    /** @var Armor $helmet */
    $helmet = Item::get(298, 0, 1);
    $helmet->setCustomColor($gear);
    /** @var Armor $chestplate */
    $chestplate = Item::get(299, 0, 1);
    $chestplate->setCustomColor($gear);
    /** @var Armor $leggings */
    $leggings = Item::get(300, 0, 1);
    $leggings->setCustomColor($gear);
    /** @var Armor $boots */
    $boots = Item::get(301, 0, 1);
    $boots->setCustomColor($gear);
    $player->getArmorInventory()->setHelmet($helmet);
    $player->getArmorInventory()->setChestplate($chestplate);
    $player->getArmorInventory()->setLeggings($leggings);
    $player->getArmorInventory()->setBoots($boots);
    $player->getArmorInventory()->sendContents($player);
    if ($this->timers[$player->getName()] < 24) {
      $this->timers[$player->getName()] = $this->timers[$player->getName()] + 1;
    } else {
      $this->timers[$player->getName()] = 0;
    }
  }

  public function clearItems(): int {
    $item = 0;
    foreach ($this->getServer()->getLevels() as $level) {
      foreach ($level->getEntities() as $entity) {
        if (!$this->isEntityExempted($entity) && !($entity instanceof Creature)) {
          $entity->close();
          $item++;
        }
      }
    }
    return $item;
  }

  public function clearMobs(): int {
    $mobs = 0;
    foreach ($this->getServer()->getLevels() as $level) {
      foreach ($level->getEntities() as $entity) {
        if (!$this->isEntityExempted($entity) && $entity instanceof Creature && !($entity instanceof Human)) {
          $entity->close();
          $mobs++;
        }
      }
    }
    return $mobs;
  }

  public function exemptEntity(Entity $entity): void {
    $this->exemptedEntities[$entity->getID()] = $entity;
  }

  public function isEntityExempted(Entity $entity): bool {
    return isset($this->exemptedEntities[$entity->getID()]);
  }
}
