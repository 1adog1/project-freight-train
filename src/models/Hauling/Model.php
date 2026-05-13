<?php

    namespace Ridley\Models\Hauling;

    class Model implements \Ridley\Interfaces\Model {
        
        private $controller;
        private $databaseConnection;
        private $userAuthorization;
        
        public $contractData = [];

        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            
            $this->controller = $this->dependencies->get("Controller");
            $this->databaseConnection = $this->dependencies->get("Database");
            $this->userAuthorization = $this->dependencies->get("Authorization Control");

            if (isset($this->controller->sourceCharacterID)) {

                $this->get_contracts();
                $this->build_quotes();

            }

        }

        private function get_contracts() {

            $charactersToCheck = [];
            $stationsToCheck = [];
            $citadelsToCheck = [];
            $distinctSystems = [];

            $esiHandler = new \Ridley\Objects\ESI\Handler(
                $this->databaseConnection,
                $this->userAuthorization->getAccessToken("Source", $this->controller->sourceCharacterID)
            );

            //
            // Getting Contract Data
            //

            $maxPage = 1;

            for ($page = 1; $page <= $maxPage; $page++) {

                $contractsRequest = $esiHandler->call(
                    endpoint: "/corporations/{corporation_id}/contracts/", 
                    corporation_id: $this->controller->sourceCorporationID, 
                    page: $page, 
                    retries: 1
                );

                if ($contractsRequest["Success"]) {

                    $maxPage = $contractsRequest["Headers"]["X-Pages"];

                    foreach ($contractsRequest["Data"] as $eachContract) {

                        if ($eachContract["type"] == "courier" and in_array($eachContract["status"], ["outstanding", "in_progress"])) {

                            $contract = new \Ridley\Objects\Calculator\Contract($eachContract);
                            $this->contractData[$eachContract["contract_id"]] = $contract;

                            if (isset($contract->acceptorID) and !in_array($contract->acceptorID, $charactersToCheck)) {
                                $charactersToCheck[] = $contract->acceptorID;
                            }
                            if (!in_array($contract->issuerID, $charactersToCheck)) {
                                $charactersToCheck[] = $contract->issuerID;
                            }

                            if ($contract->startLocationType == "Citadel" and !in_array($contract->startLocationID, $citadelsToCheck)) {
                                $citadelsToCheck[] = $contract->startLocationID;
                            }
                            elseif ($contract->startLocationType == "Station" and !in_array($contract->startLocationID, $stationsToCheck)) {
                                $stationsToCheck[] = $contract->startLocationID;
                            }

                            if ($contract->endLocationType == "Citadel" and !in_array($contract->endLocationID, $citadelsToCheck)) {
                                $citadelsToCheck[] = $contract->endLocationID;
                            }
                            elseif ($contract->endLocationType == "Station" and !in_array($contract->endLocationID, $stationsToCheck)) {
                                $stationsToCheck[] = $contract->endLocationID;
                            }

                        }
                    
                    }
                    
                }
                else {

                    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                    throw new \Exception("Failed to get corporate contracts.");

                }

            }

            //
            // Parsing Characters
            // Output: $characterData
            //

            $characterData = [];
            $idsToParse = [];

            foreach (array_chunk($charactersToCheck, 995) as $subLists) {

                $affiliationsCall = $esiHandler->call(endpoint: "/characters/affiliation/", characters: $subLists, retries: 1);

                if ($affiliationsCall["Success"]) {

                    foreach ($affiliationsCall["Data"] as $each) {

                        if (!in_array($each["character_id"], $idsToParse)) {
                            $idsToParse[] = $each["character_id"];
                        }
                        if (!in_array($each["corporation_id"], $idsToParse)) {
                            $idsToParse[] = $each["corporation_id"];
                        }
                        if (isset($each["alliance_id"]) and !in_array($each["alliance_id"], $idsToParse)) {
                            $idsToParse[] = $each["alliance_id"];
                        }

                        $characterData[$each["character_id"]] = [
                            "Character ID" => $each["character_id"],
                            "Character Name" => null,
                            "Corporation ID" => $each["corporation_id"],
                            "Corporation Name" => null,
                            "Alliance ID" => ($each["alliance_id"]) ?? null,
                            "Alliance Name" => null
                        ];

                    }

                }
                else {

                    throw new \Exception("Failed to get affiliations for contract characters.");

                }

            }


            $parsedIDs = [];

            foreach (array_chunk($idsToParse, 995) as $subLists) {

                $namesCall = $esiHandler->call(endpoint: "/universe/names/", ids: $subLists, retries: 1);

                if ($namesCall["Success"]) {

                    foreach ($namesCall["Data"] as $each) {

                        $parsedIDs[$each["id"]] = $each["name"];

                    }

                }
                else {

                    throw new \Exception("Failed to get names for contract characters.");

                }

            }

            foreach ($characterData as $eachID => &$eachData) {
                $eachData["Character Name"] = $parsedIDs[$eachData["Character ID"]] ?? "Unknown Character";
                $eachData["Corporation Name"] = $parsedIDs[$eachData["Corporation ID"]] ?? "Unknown Corporation";
                $eachData["Alliance Name"] = $parsedIDs[$eachData["Alliance ID"]] ?? null;
            }

            //
            // Parsing Citadels
            // Output: $citadelData
            //

            $citadelData = [];

            foreach ($citadelsToCheck as $eachCitadel) {

                $citadelsCall = $esiHandler->call(endpoint: "/universe/structures/{structure_id}/", structure_id: $eachCitadel, retries: 1);

                if ($citadelsCall["Success"]) {

                    $citadelData[$eachCitadel] = $citadelsCall["Data"];

                    if (!in_array($citadelsCall["Data"]["solar_system_id"], $distinctSystems)) {
                        $distinctSystems[] = $citadelsCall["Data"]["solar_system_id"];
                    }

                }

            }

            //
            // Parsing Stations
            // Output: $stationData
            //

            $stationData = [];

            foreach ($stationsToCheck as $eachStation) {

                $stationsCall = $esiHandler->call(endpoint: "/universe/stations/{station_id}/", station_id: $eachStation, retries: 1);

                if ($stationsCall["Success"]) {

                    $stationData[$eachStation] = $stationsCall["Data"];

                    if (!in_array($stationsCall["Data"]["system_id"], $distinctSystems)) {
                        $distinctSystems[] = $stationsCall["Data"]["system_id"];
                    }

                }
                else {

                    throw new \Exception("Failed to data for a contract station.");

                }

            }

            //
            // Get System Names
            // Output: $systemData
            //

            $systemData = [];

            if (!empty($distinctSystems)) {

                $systemCounter = 0;
                $queryConditions = [];
                foreach ($distinctSystems as $eachSystem) {
                    $queryConditions[(":system_" . $systemCounter++)] = $eachSystem;
                    $systemCounter++;
                }

                $checkQuery = $this->databaseConnection->prepare("
                    SELECT id, name, regionid, regionname FROM evesystems WHERE id IN (" . implode(", ", array_keys($queryConditions)) . ")
                ");
                foreach ($queryConditions as $eachPlaceholder => $eachSystem) {
                    $checkQuery->bindValue($eachPlaceholder, $eachSystem, \PDO::PARAM_INT);
                }
                $checkQuery->execute();

                while ($queryData = $checkQuery->fetch(\PDO::FETCH_ASSOC)) {

                    $systemData[$queryData["id"]] = $queryData;

                }

            }

            //
            // Populating Contract Data
            //

            foreach ($this->contractData as $eachID => &$eachContract) {

                if (isset($eachContract->acceptorID)) {
                    $eachContract->acceptorData = $characterData[$eachContract->acceptorID];
                }

                $eachContract->issuerData = $characterData[$eachContract->issuerID];

                if ($eachContract->startLocationType == "Citadel" and isset($citadelData[$eachContract->startLocationID])) {

                    $startData = $citadelData[$eachContract->startLocationID];
                    $eachContract->startLocation = $startData["name"];
                    $eachContract->startSystemID = $startData["solar_system_id"];
                    $eachContract->startSystem = $systemData[$eachContract->startSystemID]["name"];

                }
                elseif ($eachContract->startLocationType == "Station") {

                    $startData = $stationData[$eachContract->startLocationID];
                    $eachContract->startLocation = $startData["name"];
                    $eachContract->startSystemID = $startData["system_id"];
                    $eachContract->startSystem = $systemData[$eachContract->startSystemID]["name"];

                }

                if ($eachContract->endLocationType == "Citadel" and isset($citadelData[$eachContract->endLocationID])) {

                    $endData = $citadelData[$eachContract->endLocationID];
                    $eachContract->endLocation = $endData["name"];
                    $eachContract->endSystemID = $endData["solar_system_id"];
                    $eachContract->endSystem = $systemData[$eachContract->endSystemID]["name"];

                }
                elseif ($eachContract->endLocationType == "Station") {

                    $endData = $stationData[$eachContract->endLocationID];
                    $eachContract->endLocation = $endData["name"];
                    $eachContract->endSystemID = $endData["system_id"];
                    $eachContract->endSystem = $systemData[$eachContract->endSystemID]["name"];

                }

            }

        }

        private function build_quotes() {

            $calculator = new \Ridley\Objects\Calculator\Calculator($this->dependencies);

            foreach ($this->contractData as $eachID => &$eachContract) {

                if (isset($eachContract->startSystem) and isset($eachContract->endSystem)) {

                    $eachContract->quote = $calculator->getQuote(
                        $eachContract->startSystem, 
                        $eachContract->endSystem, 
                        (int)$eachContract->collateral, 
                        (int)round($eachContract->volume)
                    );
                    $eachContract->build_issues_and_standing();

                }

            }

        }

    }

?>