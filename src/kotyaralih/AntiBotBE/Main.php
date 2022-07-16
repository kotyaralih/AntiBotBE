<?php

namespace kotyaralih\AntiBotBE;

use pocketmine\event\Listener;
use pocketmine\event\player\{PlayerPreLoginEvent, PlayerQuitEvent, PlayerMoveEvent, PlayerChatEvent};
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener{

    private $IPs = [];
    private $moved = [];
    private $msgs = [];
    private $susscore = [];

    public function onEnable() : void{
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    public function onMove(PlayerMoveEvent $event){
        if($event->getPlayer()->hasPermission("antibotbe.bypass")) return;
    	if($this->getConfig()->get("anti-spam-bots", true) and !isset($this->moved[$event->getPlayer()->getName()])){
	    	$this->moved[$event->getPlayer()->getName()] = 1;
			if(isset($this->msgs[$event->getPlayer()->getName()])){
				unset($this->msgs[$event->getPlayer()->getName()]);
			}
		}
	}
	
	public function onChat(PlayerChatEvent $event){
		if(!$event->isCancelled()){
                        if($event->getPlayer()->hasPermission("antibotbe.bypass")) return;
			if($this->getConfig()->get("anti-spam-bots", true) and !isset($this->moved[$event->getPlayer()->getName()])){
				if(!isset($this->msgs[$event->getPlayer()->getName()])){
					$this->msgs[$event->getPlayer()->getName()] = 1;
				} else {
					$this->msgs[$event->getPlayer()->getName()]++;
				}
				$event->cancel();
				if($this->getConfig()->get("notify-player", true)) $event->getPlayer()->sendMessage($this->getConfig()->get("notification"));
				if($this->msgs[$event->getPlayer()->getName()] >= 20){
					if(!isset($this->susscore[$event->getPlayer()->getNetworkSession()->getIp()])){
						$this->susscore[$event->getPlayer()->getNetworkSession()->getIp()] = 1;
					} else {
						$this->getServer()->getNetwork()->blockAddress($event->getPlayer()->getNetworkSession()->getIp(), 300);
						unset($this->susscore[$event->getPlayer()->getNetworkSession()->getIp()]);
					}
					$event->getPlayer()->kick();
					$this->getServer()->getLogger()->warning("Player " . $event->getPlayer()->getName() . " sent too many messages without moving after join!");
					$this->getServer()->getLogger()->warning("Maybe it's a bot.");
				}
			}
		}
	}

    public function onPreLogin(PlayerPreLoginEvent $event){
        // todo: bypass player by getting config the playername...
        isset($this->IPs[$ip = $event->getIp()]) ? $this->IPs[$ip] += 1 : $this->IPs[$ip] = 1;
        if($this->IPs[$ip] > $this->getConfig()->get("max-cons", 5)){
            switch($this->getConfig()->get("action", "ban-ip")){
                case "ban-ip":
                    $this->getServer()->getIPBans()->addBan($ip, "Bot Detected");
                    foreach($this->getServer()->getOnlinePlayers() as $p){
                        if($p->getNetworkSession()->getIp() === $ip){
                            $p->kick();
                        }
                    }
                break;
                case "ban-all":
                    foreach($this->getServer()->getOnlinePlayers() as $p){
                        if($p->getNetworkSession()->getIp() === $ip){
                            $this->getServer()->getNameBans()->addBan($p->getName(), "Bot Detected");
                            $p->kick();
                        }
                    }
                break;
                case "kick-new-entries":
                      $event->setKickReason(0, "disconnectionScreen.noReason");
                break;
                case "kick-all":
                    foreach($this->getServer()->getOnlinePlayers() as $p){
                        if($p->getNetworkSession()->getIp() === $ip){
                            $p->kick();
                        }
                    }
                break;
            }
            $event->setKickReason(0, "disconnectionScreen.noReason");
        }
    }

    public function onQuit(PlayerQuitEvent $event){
        if($event->getPlayer()->hasPermission("antibotbe.bypass")) return;
        if(isset($this->IPs[$event->getPlayer()->getNetworkSession()->getIp()])){
            $this->IPs[$event->getPlayer()->getNetworkSession()->getIp()] -= 1;
            if(isset($this->moved[$event->getPlayer()->getName()])){
            	unset($this->moved[$event->getPlayer()->getName()]);
            }
        }
    }

}
