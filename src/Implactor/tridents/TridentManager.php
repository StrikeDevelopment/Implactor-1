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
namespace Implactor\tridents;

use pocketmine\item\{Item as TridentItem, ItemFactory as ItemManager};
use pocketmine\entity\Entity as TridentEntity;

class TridentManager extends TridentEntity {

  public static function init(): void {
    TridentEntity::registerEntity(ThrownTrident::class, true, ["Thrown Trident", "minecraft:thrown_trident"]);
    ItemManager::registerItem(new Trident(), true);
    TridentItem::initCreativeItems();
  }
}
