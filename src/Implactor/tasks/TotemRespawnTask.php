<?php
declare(strict_types=1);
namespace Implactor\tasks;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\LevelEventPacket as RespawnPacket;

use Implactor\Implade;

class TotemRespawnTask extends ImpladeTask {

  private $player;
  private $plugin;

  public function __construct(Implade $plugin, Player $player) {
    $this->plugin = $plugin;
    $this->player = $player;
  }

  public function onRun(int $currentTick): void {
    $player = $this->player;
    if ($this->plugin->getImplade()->get("totem-respawn") == true) {
      $packetRespawn = new RespawnPacket();
      $packetRespawn->evid = RespawnPacket::EVENT_SOUND_TOTEM;
      $packetRespawn->data = 0;
      $packetRespawn->position = $player->asVector3();
      $player->sendDataPacket($packetRespawn);
    }
  }
}