<?php

    namespace Ridley\Controllers\Manager;

    class Controller implements \Ridley\Interfaces\Controller {

        private $databaseConnection;
        private $logger;
        private $csrfToken;
        public $errors = [];

        //General Settings
        public $contractCorporation;
        //Restrictions
        public $onlyApprovedRoutes;
        public $allowHighsecToHighsec;
        public $allowLowsec;
        public $allowNullsec;
        public $allowWormholes;
        public $allowPochven;
        public $allowRush;
        //Timing Controls
        public $contractExpiration;
        public $contractTimeToComplete;
        public $rushContractExpiration;
        public $rushContractTimeToComplete;
        //Pricing Controls
        public $maxThresholdPrice;
        public $gatePrice;
        public $wormholePrice;
        public $pochvenPrice;
        //Volume Controls
        public $maxVolume;
        public $blockadeRunnerCutoff;
        public $highsecToHighsecMaxVolume;
        public $maxWormholeVolume;
        public $maxPochvenVolume;
        //Collateral Controls
        public $maxCollateral;
        public $collateralPremium;
        //Collateral Penalty Controls
        public $highCollateralCutoff;
        public $highCollateralPenalty;
        public $highCollateralBlockadeRunnerPenalty;
        //Multiplier Controls
        public $rushMultiplier;
        public $nonstandardMultiplier;

        public $quoteRequested = false;
        public $volume;
        public $collateral;
        public $price;
        public $corporation;
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {

            $this->databaseConnection = $this->dependencies->get("Database");
            $this->logger = $this->dependencies->get("Logging");
            $this->csrfToken = $this->dependencies->get("CSRF Token");
            
            if ($this->loadOptions()) {

                if ($_SERVER["REQUEST_METHOD"] == "POST") {

                    if (isset($_POST["csrf_token"]) and $_POST["csrf_token"] === $this->csrfToken) {

                        if (isset($_POST["Action"])) {

                            if ($_POST["Action"] == "Update_Settings") {

                                $allNumericVariablesPresent = true;
                                $numericVariables = [
                                    "contractExpiration",
                                    "contractTimeToComplete",
                                    "rushContractExpiration",
                                    "rushContractTimeToComplete",
                                    "maxVolume", 
                                    "maxCollateral", 
                                    "blockadeRunnerCutoff", 
                                    "maxThresholdPrice", 
                                    "gatePrice", 
                                    "highsecToHighsecMaxVolume", 
                                    "maxWormholeVolume", 
                                    "maxPochvenVolume", 
                                    "rushMultiplier", 
                                    "nonstandardMultiplier", 
                                    "wormholePrice", 
                                    "pochvenPrice", 
                                    "collateralPremium",
                                    "highCollateralCutoff",
                                    "highCollateralPenalty",
                                    "highCollateralBlockadeRunnerPenalty"
                                ];

                                foreach ($numericVariables as $each) {
                                    if (!isset($_POST[$each]) or !is_numeric($_POST[$each])) {
                                        $allNumericVariablesPresent = false;
                                        break;
                                    }
                                }

                                if (isset($_POST["contractCorporation"]) and $_POST["contractCorporation"] != "" and $allNumericVariablesPresent) {
                                    $this->updateOptions();
                                    $this->loadOptions();
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "Options Failed to Update! All options must be set and numeric options must be numeric.";
                                }

                            }
                            elseif ($_POST["Action"] == "Add_Tier") {

                                if (isset($_POST["tier_range"]) and $_POST["tier_range"] != "" and isset($_POST["tier_price"]) and $_POST["tier_price"] != "") {
                                    $this->addOrRemoveTier("Add", $_POST["tier_range"], $_POST["tier_price"]);
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "Tier Failed to Add! Both a threshold and price must be included.";
                                }

                            }
                            elseif ($_POST["Action"] == "Remove_Tier") {

                                if (isset($_POST["old_tier_range"]) and $_POST["old_tier_range"] != "") {
                                    $this->addOrRemoveTier("Remove", $_POST["old_tier_range"]);
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "Tier Failed to Remove! No threshold was sent.";
                                }

                            }
                            elseif ($_POST["Action"] == "Add_Restricted_Region") {

                                if (isset($_POST["new_region_restriction"]) and $_POST["new_region_restriction"] != "") {
                                    $this->addOrRemoveRestriction("Add", "Region", $_POST["new_region_restriction"]);
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "Region Failed to Add! No name was sent.";
                                }

                            }
                            elseif ($_POST["Action"] == "Remove_Restricted_Region") {

                                if (isset($_POST["old_region_restriction"]) and $_POST["old_region_restriction"] != "") {
                                    $this->addOrRemoveRestriction("Remove", "Region", $_POST["old_region_restriction"]);
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "Region Failed to Remove! No name was sent.";
                                }

                            }
                            elseif ($_POST["Action"] == "Add_Restricted_System") {

                                if (isset($_POST["new_system_restriction"]) and $_POST["new_system_restriction"] != "") {
                                    $this->addOrRemoveRestriction("Add", "System", $_POST["new_system_restriction"]);
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "System Failed to Add! No name was sent.";
                                }

                            }
                            elseif ($_POST["Action"] == "Remove_Restricted_System") {

                                if (isset($_POST["old_system_restriction"]) and $_POST["old_system_restriction"] != "") {
                                    $this->addOrRemoveRestriction("Remove", "System", $_POST["old_system_restriction"]);
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "System Failed to Remove! No name was sent.";
                                }

                            }
                            elseif ($_POST["Action"] == "Add_Route") {

                                if (
                                    isset($_POST["route_origin"]) 
                                    and $_POST["route_origin"] != "" 
                                    and isset($_POST["route_destination"]) 
                                    and $_POST["route_destination"] != "" 
                                    and isset($_POST["route_price_model"])
                                    and in_array($_POST["route_price_model"], ["Standard", "Fixed", "Range", "Gate"])
                                ) {
                                    $this->addOrRemoveRoute(
                                        "Add", 
                                        $_POST["route_origin"], 
                                        $_POST["route_destination"], 
                                        $_POST["route_price_model"], 
                                        ((isset($_POST["route_price"]) and $_POST["route_price"] != "") ? $_POST["route_price"] : null), 
                                        ((isset($_POST["route_gate_price"]) and $_POST["route_gate_price"] != "") ? $_POST["route_gate_price"] : null), 
                                        ((isset($_POST["route_premium"]) and $_POST["route_premium"] != "") ? $_POST["route_premium"] : null), 
                                        ((isset($_POST["route_max_volume"]) and $_POST["route_max_volume"] != "") ? $_POST["route_max_volume"] : null),
                                        ((isset($_POST["route_max_collateral"]) and $_POST["route_max_collateral"] != "") ? $_POST["route_max_collateral"] : null),
                                        isset($_POST["route_add_inverse"])
                                    );
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "Route Failed to Add! An origin, destination, and price model must be included.";
                                }

                            }
                            elseif ($_POST["Action"] == "Remove_Route") {

                                if (isset($_POST["old_route_origin"]) and $_POST["old_route_origin"] != "" and isset($_POST["old_route_destination"]) and $_POST["old_route_destination"] != "") {
                                    $this->addOrRemoveRoute(
                                        "Remove", 
                                        $_POST["old_route_origin"], 
                                        $_POST["old_route_destination"]
                                    );
                                }
                                else {
                                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                    $this->errors[] = "Route Failed to Remove! An origin and destination combination was not sent.";
                                }

                            }
                            else {

                                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                                throw new \Exception("No valid combination of action and required secondary arguments was received.", 10002);
            
                            }
        
                        }
                        else {
            
                            header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                            throw new \Exception("Request is missing the action argument.", 10001);
            
                        }

                    }
                    else {
                        header($_SERVER["SERVER_PROTOCOL"] . " 403 Forbidden");
                        $this->errors[] = "CSRF Token Mismatch! Reload the page to make further changes.";
                    }

                }

            }
            
        }

        private function updateOptions() {

            try {
                $optionUpdate = $this->databaseConnection->prepare(
                    "INSERT INTO options (
                        contractcorporation, 
                        onlyapprovedroutes, 
                        allowhighsectohighsec, 
                        allowlowsec, 
                        allownullsec, 
                        allowwormholes, 
                        allowpochven, 
                        allowrush, 
                        contractexpiration,
                        contracttimetocomplete,
                        rushcontractexpiration,
                        rushcontracttimetocomplete,
                        rushmultiplier, 
                        nonstandardmultiplier, 
                        maxvolume, 
                        maxcollateral, 
                        blockaderunnercutoff, 
                        maxthresholdprice, 
                        highsectohighsecmaxvolume, 
                        gateprice, 
                        maxwormholevolume, 
                        wormholeprice, 
                        maxpochvenvolume, 
                        pochvenprice, 
                        collateralpremium,
                        highcollateralcutoff,
                        highcollateralpenalty,
                        highcollateralblockaderunnerpenalty
                    ) VALUES (
                        :contractcorporation, 
                        :onlyapprovedroutes, 
                        :allowhighsectohighsec, 
                        :allowlowsec, 
                        :allownullsec, 
                        :allowwormholes, 
                        :allowpochven, 
                        :allowrush, 
                        :contractexpiration,
                        :contracttimetocomplete,
                        :rushcontractexpiration,
                        :rushcontracttimetocomplete,
                        :rushmultiplier, 
                        :nonstandardmultiplier, 
                        :maxvolume, 
                        :maxcollateral, 
                        :blockaderunnercutoff, 
                        :maxthresholdprice, 
                        :highsectohighsecmaxvolume, 
                        :gateprice, 
                        :maxwormholevolume, 
                        :wormholeprice, 
                        :maxpochvenvolume, 
                        :pochvenprice, 
                        :collateralpremium,
                        :highcollateralcutoff,
                        :highcollateralpenalty,
                        :highcollateralblockaderunnerpenalty
                    )"
                );
                $optionUpdate->bindParam(":contractcorporation", $_POST["contractCorporation"]);
                $optionUpdate->bindValue(":onlyapprovedroutes", (int)isset($_POST["onlyApprovedRoutes"]), \PDO::PARAM_INT);
                $optionUpdate->bindValue(":allowhighsectohighsec", (int)isset($_POST["allowHighsecToHighsec"]), \PDO::PARAM_INT);
                $optionUpdate->bindValue(":allowlowsec", (int)isset($_POST["allowLowsec"]), \PDO::PARAM_INT);
                $optionUpdate->bindValue(":allownullsec", (int)isset($_POST["allowNullsec"]), \PDO::PARAM_INT);
                $optionUpdate->bindValue(":allowwormholes", (int)isset($_POST["allowWormholes"]), \PDO::PARAM_INT);
                $optionUpdate->bindValue(":allowpochven", (int)isset($_POST["allowPochven"]), \PDO::PARAM_INT);
                $optionUpdate->bindValue(":allowrush", (int)isset($_POST["allowRush"]), \PDO::PARAM_INT);
                $optionUpdate->bindParam(":contractexpiration", $_POST["contractExpiration"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":contracttimetocomplete", $_POST["contractTimeToComplete"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":rushcontractexpiration", $_POST["rushContractExpiration"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":rushcontracttimetocomplete", $_POST["rushContractTimeToComplete"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":rushmultiplier", $_POST["rushMultiplier"]);
                $optionUpdate->bindParam(":nonstandardmultiplier", $_POST["nonstandardMultiplier"]);
                $optionUpdate->bindParam(":maxvolume", $_POST["maxVolume"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":maxcollateral", $_POST["maxCollateral"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":blockaderunnercutoff", $_POST["blockadeRunnerCutoff"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":maxthresholdprice", $_POST["maxThresholdPrice"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":highsectohighsecmaxvolume", $_POST["highsecToHighsecMaxVolume"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":gateprice", $_POST["gatePrice"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":maxwormholevolume", $_POST["maxWormholeVolume"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":wormholeprice", $_POST["wormholePrice"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":maxpochvenvolume", $_POST["maxPochvenVolume"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":pochvenprice", $_POST["pochvenPrice"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":collateralpremium", $_POST["collateralPremium"]);
                $optionUpdate->bindParam(":highcollateralcutoff", $_POST["highCollateralCutoff"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":highcollateralpenalty", $_POST["highCollateralPenalty"], \PDO::PARAM_INT);
                $optionUpdate->bindParam(":highcollateralblockaderunnerpenalty", $_POST["highCollateralBlockadeRunnerPenalty"], \PDO::PARAM_INT);
                $optionUpdate->execute();

                $newValues = $_POST;
                unset($newValues["csrf_token"]);
                unset($newValues["Action"]);

                $this->logger->make_log_entry(
                    logType: "Options Updated",
                    logDetails: print_r($newValues, true)
                );

            }
            catch (\Exception $error) {
                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                $this->errors[] = "Options Failed to Update! " . $error->getMessage();
            }
            
        }

        private function addOrRemoveTier($action, $threshold, $price = null) {

            if ($action == "Add") {
                
                try {
                    $tierAddition = $this->databaseConnection->prepare("INSERT INTO tiers (threshold, price) VALUES (:threshold, :price)");
                    $tierAddition->bindParam(":threshold", $threshold);
                    $tierAddition->bindParam(":price", $price, \PDO::PARAM_INT);
                    $tierAddition->execute();

                    $this->logger->make_log_entry(
                        logType: "Tier Added",
                        logDetails: "Threshold: $threshold LY \nPrice: $price ISK"
                    );
                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Tier Failed to Add! " . $error->getMessage();
                }

            }
            elseif ($action == "Remove") {
                
                try {
                    $tierRemoval = $this->databaseConnection->prepare("DELETE FROM tiers WHERE threshold = :threshold");
                    $tierRemoval->bindParam(":threshold", $threshold);
                    $tierRemoval->execute();

                    $this->logger->make_log_entry(
                        logType: "Tier Removed",
                        logDetails: "Threshold: $threshold LY"
                    );
                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Tier Failed to Remove! " . $error->getMessage();
                }

            }
            else {
                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                throw new \Exception("An Incorrect Action was Passed.", 11001);
                return;
            }
            
        }

        private function addOrRemoveRestriction($action, $type, $name) {

            $id = $this->getLocationID($name, $type);

            if ($action == "Add") {
                
                try {
                    $restrictionAddition = $this->databaseConnection->prepare("INSERT INTO restrictedlocations (id, type) VALUES (:id, :type)");
                    $restrictionAddition->bindParam(":id", $id);
                    $restrictionAddition->bindParam(":type", $type);
                    $restrictionAddition->execute();

                    $this->logger->make_log_entry(
                        logType: "Restriction Added",
                        logDetails: "ID: $id \nType: $type \nName: $name"
                    );
                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Restriction Failed to Add! " . $error->getMessage();
                }

            }
            elseif ($action == "Remove") {
                
                try {
                    $restrictionRemoval = $this->databaseConnection->prepare("DELETE FROM restrictedlocations WHERE id = :id");
                    $restrictionRemoval->bindParam(":id", $id);
                    $restrictionRemoval->execute();

                    $this->logger->make_log_entry(
                        logType: "Restriction Removed",
                        logDetails: "ID: $id \nType: $type \nName: $name"
                    );
                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Restriction Failed to Remove! " . $error->getMessage();
                }

            }
            else {
                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                throw new \Exception("An Incorrect Action was Passed.", 11001);
                return;
            }
            
        }

        private function addOrRemoveRoute(
            $action, 
            $origin, 
            $destination, 
            $priceModel = null, 
            $priceOverride = null, 
            $gatePriceOverride = null, 
            $collateralOverride = null, 
            $maxVolumeOverride = null, 
            $maxCollateralOverride = null,
            $addInverse = false
        ) {

            $originID = $this->getLocationID($origin, "System");
            $destinationID = $this->getLocationID($destination, "System");

            if ($action == "Add") {
                
                if (
                    !(is_null($priceOverride) or is_numeric($priceOverride))
                    or !(is_null($gatePriceOverride) or is_numeric($gatePriceOverride))
                    or !(is_null($collateralOverride) or is_numeric($collateralOverride))
                    or !(is_null($maxVolumeOverride) or is_numeric($maxVolumeOverride))
                    or !(is_null($maxCollateralOverride) or is_numeric($maxCollateralOverride))
                ) {
                    $this->errors[] = "Route Failed to Add! One or more numeric parameters were not numeric.";
                    return;
                }

                try {
                    $restrictionAddition = $this->databaseConnection->prepare(
                        "INSERT INTO routes (
                            start, 
                            end, 
                            basepriceoverride, 
                            gatepriceoverride, 
                            pricemodel, 
                            collateralpremiumoverride, 
                            maxvolumeoverride, 
                            maxcollateraloverride
                        ) VALUES (
                            :start, 
                            :end, 
                            :basepriceoverride, 
                            :gatepriceoverride, 
                            :pricemodel, 
                            :collateralpremiumoverride, 
                            :maxvolumeoverride, 
                            :maxcollateraloverride
                        )"
                    );
                    $restrictionAddition->bindParam(":start", $originID);
                    $restrictionAddition->bindParam(":end", $destinationID);
                    $restrictionAddition->bindParam(":basepriceoverride", $priceOverride);
                    $restrictionAddition->bindParam(":gatepriceoverride", $gatePriceOverride);
                    $restrictionAddition->bindParam(":pricemodel", $priceModel);
                    $restrictionAddition->bindParam(":collateralpremiumoverride", $collateralOverride);
                    $restrictionAddition->bindParam(":maxvolumeoverride", $maxVolumeOverride);
                    $restrictionAddition->bindParam(":maxcollateraloverride", $maxCollateralOverride);
                    $restrictionAddition->execute();

                    $this->logger->make_log_entry(
                        logType: "Route Added",
                        logDetails: "Origin: $origin \nDestination: $destination"
                    );
                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Route Failed to Add! " . $error->getMessage();
                }

                if ($addInverse) {

                    try {
                        $restrictionAddition = $this->databaseConnection->prepare(
                            "INSERT INTO routes (
                                start, 
                                end, 
                                basepriceoverride, 
                                gatepriceoverride, 
                                pricemodel, 
                                collateralpremiumoverride, 
                                maxvolumeoverride, 
                                maxcollateraloverride
                            ) VALUES (
                                :start, 
                                :end, 
                                :basepriceoverride, 
                                :gatepriceoverride, 
                                :pricemodel, 
                                :collateralpremiumoverride, 
                                :maxvolumeoverride, 
                                :maxcollateraloverride
                            )"
                        );
                        $restrictionAddition->bindParam(":start", $destinationID);
                        $restrictionAddition->bindParam(":end", $originID);
                        $restrictionAddition->bindParam(":basepriceoverride", $priceOverride);
                        $restrictionAddition->bindParam(":gatepriceoverride", $gatePriceOverride);
                        $restrictionAddition->bindParam(":pricemodel", $priceModel);
                        $restrictionAddition->bindParam(":collateralpremiumoverride", $collateralOverride);
                        $restrictionAddition->bindParam(":maxvolumeoverride", $maxVolumeOverride);
                        $restrictionAddition->bindParam(":maxcollateraloverride", $maxCollateralOverride);
                        $restrictionAddition->execute();

                        $this->logger->make_log_entry(
                            logType: "Route Added",
                            logDetails: "Origin: $destination \nDestination: $origin"
                        );
                    }
                    catch (\Exception $error) {
                        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                        $this->errors[] = "Inverse Route Failed to Add! " . $error->getMessage();
                    }

                }

            }
            elseif ($action == "Remove") {
                
                try {
                    $restrictionRemoval = $this->databaseConnection->prepare("DELETE FROM routes WHERE start = :start AND end = :end");
                    $restrictionRemoval->bindParam(":start", $originID);
                    $restrictionRemoval->bindParam(":end", $destinationID);
                    $restrictionRemoval->execute();

                    $this->logger->make_log_entry(
                        logType: "Route Removed",
                        logDetails: "Origin: $origin \nDestination: $destination"
                    );
                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Route Failed to Remove! " . $error->getMessage();
                }

            }
            else {
                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                throw new \Exception("An Incorrect Action was Passed.", 11001);
                return;
            }
            
        }

        private function getLocationID($name, $type) {

            if ($type == "System") {

                try {
                    $systemQuery = $this->databaseConnection->prepare("SELECT id FROM evesystems WHERE name = :name LIMIT 1");
                    $systemQuery->bindParam(":name", $name);
                    $systemQuery->execute();

                    $result = $systemQuery->fetchColumn();

                    if ($result !== false) {
                        return $result;
                    }
                    else {
                        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                        $this->errors[] = "Failed to Parse System Name!";
                        return null;
                    }

                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Failed to Parse System Name! " . $error->getMessage();
                    return null;
                }

            }
            elseif ($type == "Region") {

                try {
                    $regionQuery = $this->databaseConnection->prepare("SELECT regionid FROM evesystems WHERE regionname = :regionname LIMIT 1");
                    $regionQuery->bindParam(":regionname", $name);
                    $regionQuery->execute();

                    $result = $regionQuery->fetchColumn();

                    if ($result !== false) {
                        return $result;
                    }
                    else {
                        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                        $this->errors[] = "Failed to Parse Region Name!";
                        return null;
                    }

                }
                catch (\Exception $error) {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Failed to Parse Region Name! " . $error->getMessage();
                    return null;
                }

            }
            else {
                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                throw new \Exception("An Incorrect Type was Passed.", 11002);
                return;
            }

        }
        
        private function loadOptions() {
            
            $optionQuery = $this->databaseConnection->prepare("SELECT * FROM options ORDER BY iteration DESC LIMIT 1");
            $optionQuery->execute();
            $optionData = $optionQuery->fetch(\PDO::FETCH_ASSOC);

            if (!empty($optionData)) {

                //General Settings
                $this->contractCorporation = $optionData["contractcorporation"];
                //Restrictions
                $this->onlyApprovedRoutes = boolval($optionData["onlyapprovedroutes"]);
                $this->allowHighsecToHighsec = boolval($optionData["allowhighsectohighsec"]);
                $this->allowLowsec = boolval($optionData["allowlowsec"]);
                $this->allowNullsec = boolval($optionData["allownullsec"]);
                $this->allowWormholes = boolval($optionData["allowwormholes"]);
                $this->allowPochven = boolval($optionData["allowpochven"]);
                $this->allowRush = boolval($optionData["allowrush"]);
                //Timing Controls
                $this->contractExpiration = $optionData["contractexpiration"];
                $this->contractTimeToComplete = $optionData["contracttimetocomplete"];
                $this->rushContractExpiration = $optionData["rushcontractexpiration"];
                $this->rushContractTimeToComplete = $optionData["rushcontracttimetocomplete"];
                //Pricing Controls
                $this->maxThresholdPrice = $optionData["maxthresholdprice"];
                $this->gatePrice = $optionData["gateprice"];
                $this->wormholePrice = $optionData["wormholeprice"];
                $this->pochvenPrice = $optionData["pochvenprice"];
                //Volume Controls
                $this->maxVolume = $optionData["maxvolume"];
                $this->blockadeRunnerCutoff = $optionData["blockaderunnercutoff"];
                $this->highsecToHighsecMaxVolume = $optionData["highsectohighsecmaxvolume"];
                $this->maxWormholeVolume = $optionData["maxwormholevolume"];
                $this->maxPochvenVolume = $optionData["maxpochvenvolume"];
                //Collateral Controls
                $this->maxCollateral = $optionData["maxcollateral"];
                $this->collateralPremium = $optionData["collateralpremium"];
                //Collateral Penalty Controls
                $this->highCollateralCutoff = $optionData["highcollateralcutoff"];
                $this->highCollateralPenalty = $optionData["highcollateralpenalty"];
                $this->highCollateralBlockadeRunnerPenalty = $optionData["highcollateralblockaderunnerpenalty"];
                //Multiplier Controls
                $this->rushMultiplier = $optionData["rushmultiplier"];
                $this->nonstandardMultiplier = $optionData["nonstandardmultiplier"];

                return true;
            }
            else {
                $this->errors[] = "No routing options configured. Please run the initial setup script.";
                return false;
            }

        }
        
    }

?>