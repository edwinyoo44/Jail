<?php
/*
 * This file is a part of Jail.
 * Copyright (C) 2017 hoyinm14mc
 *
 * Jail is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jail is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jail. If not, see <http://www.gnu.org/licenses/>.
 */

namespace hoyinm14mc\jail\tasks\updater;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Utils;
use hoyinm14mc\jail\Jail;

class AsyncUpdateChecker extends AsyncTask
{

    public function __construct()
    {
    }

    public function onRun()
    {
        $arr = [];
        //Github Channel
        $git_iden = json_decode(Utils::getURL("https://api.github.com/repos/hoyinm14mc/Jail/releases"), true);
        $git_iden_latest = $git_iden[0];
        //Poggit Channel
        $serverApi = \pocketmine\API_VERSION;
        list(, $headerGroups, $httpCode) = Utils::simpleCurl("https://poggit.pmmp.io/get/Jail?api=$serverApi&prerelease", 10, [], [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_NOBODY => true
        ]);
        if ($httpCode != 302) return;
        foreach ($headerGroups as $headers) {
            foreach ($headers as $name => $value) {
                if ($name === "x-poggit-resolved-version") {
                    $arr["poggit_ver"] = $value;
                }
            }
        }
        if (!isset($arr["poggit_ver"])) throw new \Exception("API Error");
        $arr["github_ver"] = $git_iden_latest["tag_name"];
        $arr["github_desc"] = $git_iden_latest["body"];
        $key = 0;
        while ($git_iden[$key]["tag_name"] != $arr["poggit_ver"]) {
            $key++;
        }
        $arr["poggit_desc"] = $git_iden[$key]["body"];
        $this->setResult($arr);
    }

    public function onCompletion(Server $server)
    {
        $plugin = Jail::getInstance();
        $no_update = true;
        if (version_compare((strtolower($plugin->getConfig()->get("update-checker-channel")) == "poggit" ? $this->getResult()["poggit_ver"] : $this->getResult()["github_ver"]), $plugin->getDescription()->getVersion(), ">")) {
            $plugin->getLogger()->info($plugin->colorMessage("&aYour version is &coutdated&a! \n&fLatest version: &e" . (strtolower($plugin->getConfig()->get("update-checker-channel")) == "poggit" ? $this->getResult()["poggit_ver"] : $this->getResult()["github_ver"])));
            $plugin->getLogger()->info("\nUpdate details for v" . (strtolower($plugin->getConfig()->get("update-checker-channel")) == "github" ? $this->getResult()["github_desc"] : $this->getResult()["poggit_desc"]));
            $no_update = false;
        }
        if ($no_update !== false) {
            $plugin->getLogger()->info($plugin->colorMessage("&aYou are owning the &clatest &aversion of Jail."));
        }
        $plugin->getLogger()->info($plugin->colorMessage("&6The above info was fetched from the channel: &f" . (strtolower($plugin->getConfig()->get("update-checker-channel")) == "poggit" ? "Poggit" : "Github")));
    }

}

