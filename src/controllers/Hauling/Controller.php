<?php

    namespace Ridley\Controllers\Hauling;

    class Controller implements \Ridley\Interfaces\Controller {
        
        private $databaseConnection;
        private $versionVariables;
        private $logger;
        private $configVariables;

        public $sourceCharacterID;
        public $sourceCorporationID;
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            
            $this->databaseConnection = $this->dependencies->get("Database");
            $this->versionVariables = $this->dependencies->get("Version Variables");

            $this->databaseConnection = $this->dependencies->get("Database");
            $this->logger = $this->dependencies->get("Logging");
            $this->configVariables = $this->dependencies->get("Configuration Variables");
            
            if (isset($_GET["action"]) and $_GET["action"] == "login") {
                
                $auth = new \Ridley\Core\Authorization\Base\AuthBase(
                    $this->logger, 
                    $this->databaseConnection, 
                    $this->configVariables,
                    $this->versionVariables
                );
                
                $auth->login("Source", "esi-search.search_structures.v1 esi-contracts.read_corporation_contracts.v1 esi-universe.read_structures.v1");

            }

            $this->get_source();

        }

        private function get_source() {

            $sourceQuery = $this->databaseConnection->prepare("SELECT id FROM sourcecharacters WHERE status=:status");
            $sourceQuery->bindValue(":status", "Active");
            $sourceQuery->execute();

            $sourceCharacter = $sourceQuery->fetchColumn();

            if ($sourceCharacter !== false) {

                $esiHandler = new \Ridley\Objects\ESI\Handler(
                    $this->databaseConnection,
                    $this->versionVariables
                );

                $affiliationsCall = $esiHandler->call(endpoint: "/characters/affiliation/", characters: [$sourceCharacter], retries: 1);

                if ($affiliationsCall["Success"]) {

                    foreach ($affiliationsCall["Data"] as $each) {

                        $this->sourceCorporationID = $each["corporation_id"];

                    }

                }
                else {

                    throw new \Exception("Failed to get source character affiliation.");

                }

                $this->sourceCharacterID = $sourceCharacter;
            }

        }

    }

?>