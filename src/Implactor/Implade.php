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

use pocketmine\entity\{Entity, Creature, Human};
use pocketmine\item\{Item, Armor};
use pocketmine\{Player, Server};
use pocketmine\utils\{Config, Color};
use pocketmine\network\mcpe\protocol\{AddEntityPacket, LevelSoundEventPacket};

use pocketmine\command\{Command, CommandSender};
use pocketmine\plugin\{PluginBase, PluginDescription};
use pocketmine\nbt\StringTag;
use pocketmine\level\sound\DoorBumpSound;
use pocketmine\math\Vector3;
use pocketmine\event\Listener;

use Implactor\{FormManagsr, EntityManager};
use Implactor\entities\{BotHuman, DeathHuman, SoccerMagma};
use Implactor\listeners\{AntiAdvertising, AntiCaps, AntiSwearing, BotListener, EventListener, HeadListener};
use Implactor\tasks\ClearLaggTask;
use Implactor\particles\SpawnParticles;
use Implactor\tridents\TridentManager;
use onebone\economyapi\EconomyAPI;
use jojoe77777\FormAPI\FormAPI;

class Implade extends PluginBase implements Listener {

  const VERSION = 4;

  protected $lang;
  public $forms; // Access the property to "FormsManager" file!
  protected $economy;
	
  public $exemptedEntities = [];
  public $impladePrefix = " §f§l IR ➤§r ";
  public $rainbows = array();
  public $timers = array();
  public $config;

  private static $instance;
  private $formSystem; // "FormsManager" file.
  private $customEntities; // "EntityManager" file.

  public function getImplade(): Config {
    return $this->config;
  }
	
  public function getForm(): FormsManager {
    return $this->formSystem;
  }
	
  public static function getInstance() : self {
	return self::$instance;
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
    if ($this->getImplade()->get("update-checker", true)) {
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
    if ($this->getImplade()->get('Version') < self::VERSION) {
      rename($this->getDataFolder() . "iConfig.yml", $this->getDataFolder() . "[Outdated] iConfig.yml");
      $this->getImplade()->reload();
      $this->getLogger()->notice($this->getLang("outdated-config-message"));
    }
  }

  public function onEnable(): void {
    // Check Managers \\
    $this->checkDepends();
    $this->checkEntities();
    $this->checkTridents();
    $this->formSystem = new FormsManager();
    $this->customEntities = new EntityManager();
	  
    // Check Listeners \\
    $this->getServer()->getPluginManager()->registerEvents($this, $this);  
    $this->getServer()->getPluginManager()->registerEvents(new AntiAdvertising($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new AntiCaps($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new AntiSwearing($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new BotListener($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    $this->getServer()->getPluginManager()->registerEvents(new HeadListener($this), $this);
	  
    self::$instance = $this;
    $this->getLogger()->info($this->getLang("license-plugin-message"));
    $this->config = new Config($this->getDataFolder() . "iConfig.yml");
	  
    // Check the other features system \\
    if (is_numeric($this->getImplade()->get("clear-timer"))) {
      $this->getScheduler()->scheduleRepeatingTask(new ClearLaggTask($this), $this->getImplade()->get("clear-timer") * 20);
    } else {
      $this->getLogger()->error($this->getLang("clearlagg-error-message"));
    }
    if ($this->getImplade()->get("spawn-particles") == true) {
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
    TridentManager::init();
  }

  public function configLanguages(): void {
    if (!file_exists($this->getDataFolder() . "languages/")) {
      @mkdir($this->getDataFolder() . "languages/");
    }
    $language = $this->getImplade()->get('language');
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

  public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
    if (!$sender instanceof Player) {
      $sender->sendMessage($this->getLang("only-command-ingame-message"));
      return true;
    }
    if (strtolower($command->getName()) === "bot") {
      if ($sender->hasPermission("implactor.bot")) {
        FormsManager::getForm()->botMenu($sender);
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "soccer") {
      if ($sender->hasPermission("implactor.soccer")) {
        EntityManager::getCustom()->spawnSoccer($sender);
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
        FormsManager::getForm()->rainbowMenu($sender);
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "visible") {
      if ($sender->hasPermission("implactor.visible")) {
        FormsManager::getForm()->visibleMenu($sender);
      } else {
        $sender->sendMessage($this->getLang("no-permission-message"));
        return false;
      }
    } else if (strtolower($command->getName()) === "vision") {
      if ($sender->hasPermission("implactor.vision")) {
        FormsManager::getForm()->visionMenu($sender);
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
    } else if (strtolower($command->getName()) === "head") {
      if ($sender->hasPermission("implactor.head")) {
        $headItem = $sender->getInventory()->getItemInHand();
        $headLore = $headItem->getlore();
        if ($headItem->getNamedTag()->hasTag("head", StringTag::class)) {
          $killer = $headItem->getNamedTag()->getString("head");
          $seller = $this->economy->myMoney($killer) * $this->getImplade()->get("item-head-sell-money", 100);
          $this->economy->reduceMoney($killer, $seller, true);
          $this->economy->addMoney($sender, $seller, true);
          $headItem->setCount(1);
          $sender->sendMessage($this->impladePrefix . $this->getLang("item-head-sold-message", array(
                  "%money" => $this->getImplade()->get("item-head-sell-money", 100),
                  "%target" => $target
              )));
          $sender->getInventory()->removeItem($headItem);
        } else {
          $sender->sendMessage($this->impladePrefix . $this->getLang("item-no-head-message"));
          return false;    
        }
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
          $sender->sendMessage($this->getLang("command-usage-message") ."§e/icast <". $this->getLang("broadcast-usage-argument-message") ."§e>");
          return false;     
        }
          $broadcastMsg = implode(" ", $args);
          $sender->getServer()->broadcastMessage(" §b§lImplacast ➤§r§e " . $broadcastMsg);
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
      return "§cError with {$configKey}";
    $key = strtr($key, $keys);
    return str_replace("&", "§", $key);
  }
	
  public function isSummonLightning(Player $player, $strike){
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

  public function rainbowArmor(Player $player, int $r, int $g, int $b): void {
    $gear = new Color($r, $g, $b);
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
