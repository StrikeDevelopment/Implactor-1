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

use pocketmine\item\Item;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

use Implactor\Implade;

class HeadListener implements Listener {
	
    private $plugin;
	
    public function __construct(Implade $plugin){
        $this->plugin = $plugin;
    }
    
    public function onDeath(PlayerDeathEvent $ev): void{
    	$player = $ev->getPlayer();
        $cause = $player->getLastDamageCause();
        if ($cause instanceof EntityDamageByEntityEvent) {
          $killer = $cause->getModifier();
          if ($killer instanceof Player) {
            $headItem = Item::get(Item::SKULL, mt_rand(50, 100), 1);
            $headItem->setCustomName(Implade::getInstance()->getLang("item-head-name", array(
		    "%player" => $player->getName()
	        )));
            $headItem->setLore(Implade::getInstance()->getLang("item-head-lore"));
            $headNBT = $headItem->getNamedTag()->setString("head", strtolower($player->getName()));
            $headItem->setNamedTag($headNBT);
            $killer->getInventory()->addItem($headItem);
            $killer->sendMessage(Implade::getInstance()->getLang("item-head-obtained-message", array(
		    "%player" => $player->getName()
	        )));
          }
        }
    }
}
