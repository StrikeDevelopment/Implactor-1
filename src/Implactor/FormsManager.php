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
use pocketmine\utils\Config;
use pocketmine\utils\Vector3;

use jojoe77777\FormAPI\FormAPI;

class FormsManager {
  
  private $plugin;
  private $visible = [];
  
  private static $formSystem;
  
  public function __construct(){
    self::$formSystem = $this;
  }
  
  public static function getManager() : Implade{
    return Implade::getInstance();
  }
  
  public static function getForm(): self{
    return self::$formSystem;
  }	
  
  public function visionMenu($sender): void {
    $impladeForm = self::getManager()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $sender->addEffect(new EffectInstance(Effect::getEffect(Effect::NIGHT_VISION), 1000000, 254, true));
          $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("vision-enabled-message"));
          break;
        case 1:
          $sender->removeEffect(Effect::NIGHT_VISION);
          $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("vision-disabled-message"));
          break;
        case 2:
          $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(self::getManager()->getLang("form-menu-title"));
    $impladeForm->setContent(self::getManager()->getLang("vision-content-message"));
    $impladeForm->addButton(self::getManager()->getLang("enable-message"), 1, "https://cdn.discordapp.com/attachments/442624759985864714/468316317351542804/On.png");
    $impladeForm->addButton(self::getManager()->getLang("disable-message"), 2, "https://cdn.discordapp.com/attachments/442624759985864714/468316317351542806/Off.png");
    $impladeForm->addButton(self::getManager()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $form->sendForm($sender);
  }
  
  public function visibleMenu($sender): void {
    $impladeForm = self::getManager()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $sender->addTitle(self::getManager()->getLang("visible-on-message"), self::getManager()->getLang("visible-show-message"));
          unset(self::getManager()->visible[array_search($sender->getName(), self::getManager()->visible)]);
          foreach (self::getManager()->getServer()->getOnlinePlayers() as $visibler) {
            $sender->showplayer($visibler);
          }
          break;
        case 1:
          $sender->addTitle(self::getManager()->getLang("visible-off-message"), self::getManager()->getLang("visible-hide-message"));
          self::getManager()->visible[] = $sender->getName();
          foreach (self::getManager()->getServer()->getOnlinePlayers() as $visibler) {
            $sender->hideplayer($visibler);
          }
          break;
        case 2:
          $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(self::getManager()->getLang("form-menu-title"));
    $impladeForm->setContent(self::getManager()->getLang("visible-content-message"));
    $impladeForm->addButton(self::getManager()->getLang("show-message"), 1, "https://cdn.discordapp.com/attachments/442624759985864714/468316318060249098/Show.png");
    $impladeForm->addButton(self::getManager()->getLang("hide-message"), 2, "https://cdn.discordapp.com/attachments/442624759985864714/468316318060249099/Hide.png");
    $impladeForm->addButton(self::getManager()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $impladeForm->sendForm($sender);
  }
  
  public function botMenu($sender): void {
    $impladeForm = self::getManager()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          self::getManager()->spawnBotForm($sender);
          break;
        case 1:
          self::getManager()->clearBotForm($sender);
          break;
        case 2:
          $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(self::getManager()->getLang("form-menu-title"));
    $impladeForm->setContent(self::getManager()->getLang("bot-content-message"));
    $impladeForm->addButton(self::getManager()->getLang("bot-spawn-button-message"), 1, "");
    $impladeForm->addButton(self::getManager()->getLang("bot-clear-button-message"), 2, "");
    $impladeForm->addButton(self::getManager()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $impladeForm->sendForm($sender);
  }
  
  public function spawnBotForm($sender): void {
    $impladeForm = self::getManager()->forms->createCustomForm(function (Player $sender, $result) {
      if ($result !== null) {
        self::getManager()->spawnBot($sender, $result[1]);
        $sender->getServer()->broadcastMessage("§7[§bBot§7]§f " . self::getManager()->getLang("bot-spawned-message", array("%bot" => $result[1], "%player" => $sender->getName())));
        $sender->getLevel()->addSound(new DoorBumpSound($sender));
      }
    });
    $impladeForm->setTitle(self::getManager()->getLang("form-menu-title"));
    $impladeForm->addLabel(self::getManager()->getLang("bot-label-message"));
    $impladeForm->addInput(self::getManager()->getLang("bot-input"), self::getManager()->getLang("bot-input-name"));
    $impladeForm->sendForm($sender);
  }
  
  public function clearBotForm($sender): void {
    $impladeForm = self::getManager()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          $clearBots = 0;
          foreach (self::getManager()->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
              if ($entity instanceof BotHuman) {
                $entity->close();
                $clearBots++;
              }
            }
          }
          $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("bot-success-cleared-message", array("%bots" => $clearBots)));
          break;
        case 1:
          self::getManager()->botMenu($sender);
          break;
      }
    });
    $impladeForm->setTitle(self::getManager()->getLang("form-procced-title"));
    $impladeForm->setContent(self::getManager()->getLang("bot-clear-content-message"));
    $impladeForm->addButton(self::getManager()->getLang("yes-message"), 1, "");
    $impladeForm->addButton(self::getManager()->getLang("no-message"), 2, "");
    $impladeForm->sendForm($sender);
  }
  
  public function rainbowMenu($sender): void {
    self::getManager()->rainbows[$sender->getName()] = 0;
    $impladeForm = self::getManager()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          if (self::getManager()->rainbows[$sender->getName()] === 0) {
            self::getManager()->getScheduler()->scheduleRepeatingTask(new RainbowArmorTask($this, $sender), 5);
            $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("rainbow-enabled-message"));
          }
          break;
        case 1:
          self::getManager()->disableRainbowForm($sender);
          break;
        case 2:
          $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("close-form-message"));
          break;
      }
    });
    $impladeForm->setTitle(self::getManager()->getLang("form-menu-title"));
    $impladeForm->setContent(self::getManager()->getLang("rainbow-content-message"));
    $impladeForm->addButton(self::getManager()->getLang("enable-message"), 1, "");
    $impladeForm->addButton(self::getManager()->getLang("disable-message"), 2, "");
    $impladeForm->addButton(self::getManager()->getLang("close-message"), 3, "https://cdn.discordapp.com/attachments/442624759985864714/468316717169508362/Logopit_1531725791540.png");
    $impladeForm->sendForm($sender);
  }
  
  public function disableRainbowForm($sender): void {
    $impladeForm = self::getManager()->forms->createSimpleForm(function (Player $sender, $result) {
      switch ($result) {
        case 0:
          if (self::getManager()->rainbows[$sender->getName()] === 0) {
            self::getManager()->getScheduler()->cancelTask(self::getManager()->rainbows[$sender->getName()]);
            self::getManager()->rainbows[$sender->getName()] = 0;
            $sender->getArmorInventory()->clearAll();
            $sender->sendMessage(self::getManager()->impladePrefix . self::getManager()->getLang("rainbow-disabled-message"));
          }
          break;
        case 1:
          self::getManager()->rainbowMenu($sender);
          break;
      }
    });
    $impladeForm->setTitle(self::getManager()->getLang("form-procced-title"));
    $impladeForm->setContent(self::getManager()->getLang("rainbow-disable-content-message"));
    $impladeForm->addButton(self::getManager()->getLang("yes-message"), 1, "");
    $impladeForm->addButton(self::getManager()->getLang("no-message"), 2, "");
    $impladeForm->sendForm($sender);
  }
}
