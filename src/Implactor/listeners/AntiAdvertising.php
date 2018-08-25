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
use pocketmine\event\player\PlayerChatEvent;

use Implactor\Implade;

class AntiAdvertising implements Listener {

  private $plugin;
  private $links;

  public function __construct(Implade $plugin) {
    $this->plugin = $plugin;
    $this->links = [".leet.cc", ".playmc.pe", ".net", ".com", ".us", ".co", ".co.uk", ".ddns", ".ddns.net", ".cf", ".pe", ".me", ".cc", ".ru", ".eu", ".tk", ".gq", ".ga", ".ml", ".org", ".1", ".2", ".3", ".4", ".5", ".6", ".7", ".8", ".9", "my server", "my sever", "ma server", "mah server", "ma sever", "mah sever"];
  }

  public function onChat(PlayerChatEvent $ev): void {
    $message = $ev->getMessage();
    $player = $ev->getPlayer();
    if (!$player->hasPermission("implactor.anti")) {
      foreach ($this->links as $links) {
        if (strpos($message, $links) !== false) {
          $player->sendMessage($this->plugin->getLang("anti-advertising-message"));
          $ev->setCancelled();
          return;
        }
      }
    }
  }
}
