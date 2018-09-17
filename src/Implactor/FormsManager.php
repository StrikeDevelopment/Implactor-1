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

use pocketmine\{Player, Server};
use pocketmine\item\{Item, Armor};
use pocketmine\entity\{Entity, Effect, EffectInstance};
use pocketmine\utils\{Config, Utils};
use pocketmine\utils\Vector3;

use Implactor\Implade;
use jojoe77777\FormAPI\FormAPI;

class FormsManager {
  
  private $plugin;
  private $visible = [];
  
  private static $formSystem;
  
  public function __construct() {
    self::$formSystem = $this;
  }
  
  public static function getForm(): self {
    return self::$formSystem;
  }	
  
  public function visionMenu($sender): void {
    $impladeForm = Implade::getInstance()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $sender->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 1000000, 254, true));
          $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("vision-enabled-message"));
          break;
        case 1:
          $sender->removeEffect(Effect::NIGHT_VISION);
          $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("vision-disabled-message"));
          break;
        case 2:
          $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(Implade::getInstance()->getLang("form-menu-title"));
    $impladeForm->setContent(Implade::getInstance()->getLang("vision-content-message"));
    $impladeForm->addButton(Implade::getInstance()->getLang("enable-message"), 1, "https://cdn.discordapp.com/attachments/442624759985864714/468316317351542804/On.png");
    $impladeForm->addButton(Implade::getInstance()->getLang("disable-message"), 2, "https://cdn.discordapp.com/attachments/442624759985864714/468316317351542806/Off.png");
    $impladeForm->addButton(Implade::getInstance()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $form->sendToPlayer($sender);
  }
  
  public function visibleMenu($sender): void {
    $impladeForm = Implade::getInstance()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $sender->addTitle(Implade::getInstance()->getLang("visible-on-message"), Implade::getInstance()->getLang("visible-show-message"));
          unset(Implade::getInstance()->visible[array_search($sender->getName(), Implade::getInstance()->visible)]);
          foreach (Implade::getInstance()->getServer()->getOnlinePlayers() as $visibler) {
            $sender->showplayer($visibler);
          }
          break;
        case 1:
          $sender->addTitle(Implade::getInstance()->getLang("visible-off-message"), Implade::getInstance()->getLang("visible-hide-message"));
          Implade::getInstance()->visible[] = $sender->getName();
          foreach (Implade::getInstance()->getServer()->getOnlinePlayers() as $visibler) {
            $sender->hideplayer($visibler);
          }
          break;
        case 2:
          $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(Implade::getInstance()->getLang("form-menu-title"));
    $impladeForm->setContent(Implade::getInstance()->getLang("visible-content-message"));
    $impladeForm->addButton(Implade::getInstance()->getLang("show-message"), 1, "https://cdn.discordapp.com/attachments/442624759985864714/468316318060249098/Show.png");
    $impladeForm->addButton(Implade::getInstance()->getLang("hide-message"), 2, "https://cdn.discordapp.com/attachments/442624759985864714/468316318060249099/Hide.png");
    $impladeForm->addButton(Implade::getInstance()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $impladeForm->sendToPlayer($sender);
  }
  
  public function botMenu($sender): void {
    $impladeForm = Implade::getInstance()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          Implade::getInstance()->spawnBotForm($sender);
          break;
        case 1:
          Implade::getInstance()->clearBotForm($sender);
          break;
        case 2:
          $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(Implade::getInstance()->getLang("form-menu-title"));
    $impladeForm->setContent(Implade::getInstance()->getLang("bot-content-message"));
    $impladeForm->addButton(Implade::getInstance()->getLang("bot-spawn-button-message"), 1, "");
    $impladeForm->addButton(Implade::getInstance()->getLang("bot-clear-button-message"), 2, "");
    $impladeForm->addButton(Implade::getInstance()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $impladeForm->sendToPlayer($sender);
  }
  
  public function spawnBotForm($sender): void {
    $impladeForm = Implade::getInstance()->forms->createCustomForm(function (Player $sender, $result) {
      if ($result !== null) {
        Implade::getInstance()->spawnBot($sender, $result[1]);
        $sender->getServer()->broadcastMessage("§7[§bBot§7]§f " . Implade::getInstance()->getLang("bot-spawned-message", array("%bot" => $result[1], "%player" => $sender->getName())));
        $sender->getLevel()->addSound(new DoorBumpSound($sender));
      }
    });
    $impladeForm->setTitle(Implade::getInstance()->getLang("form-menu-title"));
    $impladeForm->addLabel(Implade::getInstance()->getLang("bot-label-message"));
    $impladeForm->addInput(Implade::getInstance()->getLang("bot-input"), Implade::getInstance()->getLang("bot-input-name"));
    $impladeForm->sendToPlayer($sender);
  }
  
  public function clearBotForm($sender): void {
    $impladeForm = Implade::getInstance()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $clearBots = 0;
          foreach (Implade::getInstance()->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
              if ($entity instanceof BotHuman) {
                $entity->close();
                $clearBots++;
              }
            }
          }
          $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("bot-success-cleared-message", array("%bots" => $clearBots)));
          break;
        case 1:
          Implade::getInstance()->botMenu($sender);
          break;
      }
    });
    $impladeForm->setTitle(Implade::getInstance()->getLang("form-procced-title"));
    $impladeForm->setContent(Implade::getInstance()->getLang("bot-clear-content-message"));
    $impladeForm->addButton(Implade::getInstance()->getLang("yes-message"), 1, "");
    $impladeForm->addButton(Implade::getInstance()->getLang("no-message"), 2, "");
    $impladeForm->sendToPlayer($sender);
  }
  
  public function rainbowMenu($sender): void {
    Implade::getInstance()->rainbows[$sender->getName()] = 0;
    $impladeForm = Implade::getInstance()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          if (Implade::getInstance()->rainbows[$sender->getName()] === 0) {
            Implade::getInstance()->getScheduler()->scheduleRepeatingTask(new RainbowArmorTask($this, $sender), 5);
            $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("rainbow-enabled-message"));
          }
          break;
        case 1:
          Implade::getInstance()->disableRainbowForm($sender);
          break;
        case 2:
          $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(Implade::getInstance()->getLang("form-menu-title"));
    $impladeForm->setContent(Implade::getInstance()->getLang("rainbow-content-message"));
    $impladeForm->addButton(Implade::getInstance()->getLang("enable-message"), 1, "");
    $impladeForm->addButton(Implade::getInstance()->getLang("disable-message"), 2, "");
    $impladeForm->addButton(Implade::getInstance()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $impladeForm->sendToPlayer($sender);
  }
  
  public function disableRainbowForm($sender): void {
    $impladeForm = Implade::getInstance()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          if (Implade::getInstance()->rainbows[$sender->getName()] === 0) {
            Implade::getInstance()->getScheduler()->cancelTask(Implade::getInstance()->rainbows[$sender->getName()]);
            Implade::getInstance()->rainbows[$sender->getName()] = 0;
            $sender->getArmorInventory()->clearAll();
            $sender->sendMessage(Implade::getInstance()->impladePrefix . Implade::getInstance()->getLang("rainbow-disabled-message"));
          }
          break;
        case 1:
          Implade::getInstance()->rainbowMenu($sender);
          break;
      }
    });
    $impladeForm->setTitle(Implade::getInstance()->getLang("form-procced-title"));
    $impladeForm->setContent(Implade::getInstance()->getLang("rainbow-disable-content-message"));
    $impladeForm->addButton(Implade::getInstance()->getLang("yes-message"), 1, "");
    $impladeForm->addButton(Implade::getInstance()->getLang("no-message"), 2, "");
    $impladeForm->sendToPlayer($sender);
  }
}
