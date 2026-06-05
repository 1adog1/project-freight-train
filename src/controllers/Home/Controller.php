<?php

    namespace Ridley\Controllers\Home;

    class Controller implements \Ridley\Interfaces\Controller {

        private $databaseConnection;
        private $versionVariables;
        private $userAuthorization;
        public $errors = [];

        //Restrictions
        public $allowHighsecToHighsec;
        public $allowWormholes;
        public $allowPochven;
        public $allowRush;
        public $showRushButton;
        //Volume Controls
        public $maxVolume;
        public $blockadeRunnerCutoff;
        public $highsecToHighsecMaxVolume;
        public $maxWormholeVolume;
        public $maxPochvenVolume;
        //Collateral Controls
        public $maxCollateral;
        //Collateral Penalty Controls
        public $highCollateralCutoff;

        public $quote_requested = false;
        public $quote;

        private $sourceCharacterID;
        private $sourceCorporationID;

        public $contractData = [
            "Populated" => false,
            "Pending" => 0,
            "Completed" => [
                "Day" => 0,
                "Week" => 0,
                "Month" => 0
            ]
        ];
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {

            $this->databaseConnection = $this->dependencies->get("Database");
            $this->versionVariables = $this->dependencies->get("Version Variables");
            $this->userAuthorization = $this->dependencies->get("Authorization Control");
            
            if ($this->loadOptions()) {

                $this->getSource();

                if (isset($this->sourceCharacterID)) {
                    $this->generateContractData();
                }

                if ($_SERVER["REQUEST_METHOD"] == "POST") {

                    if (
                        isset($_POST["origin"])
                        and $_POST["origin"] != ""
                        and isset($_POST["destination"])
                        and $_POST["destination"] != ""
                        and isset($_POST["collateral"])
                        and is_numeric($_POST["collateral"])
                        and isset($_POST["volume"])
                        and is_numeric($_POST["volume"])
                    ) {

                        $this->quote = $this->generateQuote(
                            $_POST["origin"], 
                            $_POST["destination"], 
                            (int)$_POST["collateral"], 
                            (int)round($_POST["volume"]),
                            isset($_POST["rush"])
                        );

                        if (!$this->quote->valid) {
                            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                        }

                    }
                    else {

                        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                        $this->errors[] = "Failed to Process Quote! Arguments are either missing or not in a valid format.";

                    }

                }

            }
            
        }

        private function getSource() {

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

                    return;

                }

                $this->sourceCharacterID = $sourceCharacter;
            }

        }

        private function generateContractData() {

            $esiHandler = new \Ridley\Objects\ESI\Handler(
                $this->databaseConnection,
                $this->versionVariables,
                $this->userAuthorization->getAccessToken("Source", $this->sourceCharacterID)
            );

            $referenceTime = time();

            $maxPage = 1;

            for ($page = 1; $page <= $maxPage; $page++) {

                $contractsRequest = $esiHandler->call(
                    endpoint: "/corporations/{corporation_id}/contracts/", 
                    corporation_id: $this->sourceCorporationID, 
                    page: $page, 
                    retries: 1
                );

                if ($contractsRequest["Success"]) {

                    $maxPage = $contractsRequest["Headers"]["X-Pages"];

                    foreach ($contractsRequest["Data"] as $eachContract) {

                        if ($eachContract["type"] == "courier") {

                            if (in_array($eachContract["status"], ["outstanding", "in_progress"])) {
                                $this->contractData["Pending"]++;
                            }
                            elseif ($eachContract["status"] == "finished") {

                                $contractTime = strtotime($eachContract["date_completed"]);

                                if (($referenceTime - $contractTime) <= 86400) {
                                    $this->contractData["Completed"]["Day"]++;
                                }
                                if (($referenceTime - $contractTime) <= (86400 * 7)) {
                                    $this->contractData["Completed"]["Week"]++;
                                }
                                if (($referenceTime - $contractTime) <= (86400 * 30)) {
                                    $this->contractData["Completed"]["Month"]++;
                                }

                            }

                        }
                    
                    }
                    
                }
                else {

                    return;

                }

            }

            $this->contractData["Populated"] = true;

        }

        private function generateQuote($origin, $destination, $collateral, $volume, $rush) {

            $this->quote_requested = true;
            $calculator = new \Ridley\Objects\Calculator\Calculator($this->dependencies);
            return $calculator->getQuote($origin, $destination, $collateral, $volume, $rush);

        }

        private function loadOptions() {
            
            $optionQuery = $this->databaseConnection->prepare("SELECT * FROM options ORDER BY iteration DESC LIMIT 1");
            $optionQuery->execute();
            $optionData = $optionQuery->fetch(\PDO::FETCH_ASSOC);

            if (!empty($optionData)) {

                //Restrictions
                $this->allowHighsecToHighsec = boolval($optionData["allowhighsectohighsec"]);
                $this->allowWormholes = boolval($optionData["allowwormholes"]);
                $this->allowPochven = boolval($optionData["allowpochven"]);
                $this->allowRush = boolval($optionData["allowrush"]);
                //Volume Controls
                $this->maxVolume = (int)$optionData["maxvolume"];
                $this->blockadeRunnerCutoff = (int)$optionData["blockaderunnercutoff"];
                $this->highsecToHighsecMaxVolume = (int)$optionData["highsectohighsecmaxvolume"];
                $this->maxWormholeVolume = (int)$optionData["maxwormholevolume"];
                $this->maxPochvenVolume = (int)$optionData["maxpochvenvolume"];
                //Collateral Controls
                $this->maxCollateral = (int)$optionData["maxcollateral"];
                //Collateral Penalty Controls
                $this->highCollateralCutoff = (int)$optionData["highcollateralcutoff"];
                //Multiplier Controls

            }
            else {
                $this->errors[] = "No routing options configured. Please run the initial setup script.";
                return false;
            }

            $rushQuery = $this->databaseConnection->prepare("SELECT COUNT(*) FROM routes WHERE allowrushoverride='Allow'");
            $rushQuery->execute();
            $rushCount = $rushQuery->fetchColumn();

            $this->showRushButton = ($this->allowRush or ($rushCount > 0));

            return true;
            
        }
        
    }

?>