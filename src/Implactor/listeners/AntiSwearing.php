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

class AntiSwearing implements Listener {

    private $plugin;
    private $badwords;

    public function __construct(Implade $plugin) {
      $this->plugin = $plugin;
      $this->badwords = $plugin->getLang("bad-words");
    }

    public function onChat(PlayerChatEvent $ev) : void{
        $msg = $ev->getMessage();
        $player = $ev->getPlayer();
        if(!$player->hasPermission("implactor.anti")){
            foreach($this->badwords as $badwords){
                if(strpos($msg, $badwords) !== false){
                    $player->sendMessage($this->plugin->getLang("anti-swearing-message"));
                    $ev->setCancelled();
                    return;
                }
            }
        }
    }
}
