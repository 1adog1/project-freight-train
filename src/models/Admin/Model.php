<?php

    namespace Ridley\Models\Admin;

    class Model implements \Ridley\Interfaces\Model {
        
        private $databaseConnection;
        private $versionVariables;
        private $esiHandler;
        private $controller;

        private $knownGroups;
        public $activeSource = false;
        public $sourceCharacters = [];
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            
            $this->databaseConnection = $this->dependencies->get("Database");
            $this->versionVariables = $this->dependencies->get("Version Variables");
            $this->esiHandler =  new \Ridley\Objects\ESI\Handler($this->databaseConnection, $this->versionVariables);
            $this->controller = $this->dependencies->get("Controller");
            $this->knownGroups = $this->controller->passKnownGroups();
            $this->getSourceCharacters();
            
        }
        
        public function getGroups() {
            
            $activeGroups = [];
            
            foreach ($this->knownGroups as $subGroupName => $subGroups) {
                
                if (!empty($subGroups)) {
                    
                    $activeGroups[$subGroupName] = $subGroups;
                    
                }
                
            }
            
            return $activeGroups;
            
        }

        private function getSourceCharacters() {
            
            $charactersToCheck = [];
            $idsToCheck = [];
            $knownIDs = [];
            
            $populateQuery = $this->databaseConnection->prepare("SELECT * FROM sourcecharacters");
            $populateQuery->execute();
            $populateData = $populateQuery->fetchAll();
            
            if (!empty($populateData)) {
                
                foreach ($populateData as $eachCharacter) {
                    
                    $charactersToCheck[] = $eachCharacter["id"];

                    $this->sourceCharacters[$eachCharacter["id"]] = [
                        "ID" => $eachCharacter["id"],
                        "Corporation ID" => null,
                        "Alliance ID" => null,
                        "Name" => null,
                        "Corporation" => null,
                        "Alliance" => null,
                        "Active" => ($eachCharacter["status"] == "Active"),
                        "Invalid" => ($eachCharacter["status"] == "Invalid")
                    ];

                    if ($eachCharacter["status"] == "Active") {
                        $this->activeSource = true;
                    }
                    
                }

                $affiliationsCall = $this->esiHandler->call(
                    endpoint: "/characters/affiliation/",
                    characters: $charactersToCheck,
                    retries: 1
                );

                if ($affiliationsCall["Success"]) {

                    foreach ($affiliationsCall["Data"] as $eachAffiliation) {
                        
                        $this->sourceCharacters[$eachAffiliation["character_id"]]["Corporation ID"] = $eachAffiliation["corporation_id"];

                        if (!in_array($eachAffiliation["character_id"], $idsToCheck)) {
                            $idsToCheck[] = $eachAffiliation["character_id"];
                        }
                        if (!in_array($eachAffiliation["corporation_id"], $idsToCheck)) {
                            $idsToCheck[] = $eachAffiliation["corporation_id"];
                        }

                        if (isset($eachAffiliation["alliance_id"])) {
                            $this->sourceCharacters[$eachAffiliation["character_id"]]["Alliance ID"] = $eachAffiliation["alliance_id"];

                            if (!in_array($eachAffiliation["alliance_id"], $idsToCheck)) {
                                $idsToCheck[] = $eachAffiliation["alliance_id"];
                            }

                        }

                    }

                }
                else {

                    throw new \Exception("Failed to get affiliations of source characters!");

                }

                $namesCall = $this->esiHandler->call(
                    endpoint: "/universe/names/",
                    ids: $idsToCheck,
                    retries: 1
                );

                if ($namesCall["Success"]) {

                    foreach ($namesCall["Data"] as $eachName) {

                        $knownIDs[$eachName["id"]] = $eachName["name"];

                    }

                }
                else {

                    throw new \Exception("Failed to get names of source characters!");

                }
                
            }

            foreach ($this->sourceCharacters as $eachID => &$eachSource) {

                $eachSource["Name"] = $knownIDs[$eachSource["ID"]];
                $eachSource["Corporation"] = $knownIDs[$eachSource["Corporation ID"]];

                if (!is_null($eachSource["Alliance ID"])) {
                    $eachSource["Alliance"] = $knownIDs[$eachSource["Alliance ID"]];
                }

            }
            
        }
        
    }
?>