<?php

    namespace Ridley\Controllers\Manager;

    class Controller implements \Ridley\Interfaces\Controller {

        private $databaseConnection;
        public $errors = [];

        //General Settings
        public $contractCorporation;
        public $maxVolume;
        public $maxCollateral;
        public $blockadeRunnerCutoff;
        public $maxThresholdPrice;
        public $gatePrice;

        //Restrictions
        public $onlyApprovedRoutes;
        public $allowHighsecToHighsec;
        public $allowLowsec;
        public $allowNullsec;
        public $allowWormholes;
        public $allowPochven;
        public $allowRush;
        //Volume Controls
        public $highsecToHighsecMaxVolume;
        public $maxWormholeVolume;
        public $maxPochvenVolume;
        //Pricing
        public $rushMultiplier;
        public $nonstandardMultiplier;
        public $wormholePrice;
        public $pochvenPrice;
        public $collateralPremium;

        public $quoteRequested = false;
        public $volume;
        public $collateral;
        public $price;
        public $corporation;
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {

            $this->databaseConnection = $this->dependencies->get("Database");
            
            if ($this->loadOptions()) {

                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    

                    if (isset($_POST["Action"])) {

                        if ($_POST["Action"] == "Update_Settings") {

                            $allNumericVariablesPresent = true;
                            $numericVariables = [
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
                                "collateralPremium"
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
                        collateralpremium
                    ) VALUES (
                        :contractcorporation, 
                        :onlyapprovedroutes, 
                        :allowhighsectohighsec, 
                        :allowlowsec, 
                        :allownullsec, 
                        :allowwormholes, 
                        :allowpochven, 
                        :allowrush, 
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
                        :collateralpremium
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
                $optionUpdate->execute();
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
                            pricemodel, 
                            collateralpremiumoverride, 
                            maxvolumeoverride, 
                            maxcollateraloverride
                        ) VALUES (
                            :start, 
                            :end, 
                            :basepriceoverride, 
                            :pricemodel, 
                            :collateralpremiumoverride, 
                            :maxvolumeoverride, 
                            :maxcollateraloverride
                        )"
                    );
                    $restrictionAddition->bindParam(":start", $originID);
                    $restrictionAddition->bindParam(":end", $destinationID);
                    $restrictionAddition->bindParam(":basepriceoverride", $priceOverride);
                    $restrictionAddition->bindParam(":pricemodel", $priceModel);
                    $restrictionAddition->bindParam(":collateralpremiumoverride", $collateralOverride);
                    $restrictionAddition->bindParam(":maxvolumeoverride", $maxVolumeOverride);
                    $restrictionAddition->bindParam(":maxcollateraloverride", $maxCollateralOverride);
                    $restrictionAddition->execute();
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
                                pricemodel, 
                                collateralpremiumoverride, 
                                maxvolumeoverride, 
                                maxcollateraloverride
                            ) VALUES (
                                :start, 
                                :end, 
                                :basepriceoverride, 
                                :pricemodel, 
                                :collateralpremiumoverride, 
                                :maxvolumeoverride, 
                                :maxcollateraloverride
                            )"
                        );
                        $restrictionAddition->bindParam(":start", $destinationID);
                        $restrictionAddition->bindParam(":end", $originID);
                        $restrictionAddition->bindParam(":basepriceoverride", $priceOverride);
                        $restrictionAddition->bindParam(":pricemodel", $priceModel);
                        $restrictionAddition->bindParam(":collateralpremiumoverride", $collateralOverride);
                        $restrictionAddition->bindParam(":maxvolumeoverride", $maxVolumeOverride);
                        $restrictionAddition->bindParam(":maxcollateraloverride", $maxCollateralOverride);
                        $restrictionAddition->execute();
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
            $optionData = $optionQuery->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($optionData)) {

                //General Settings
                $this->contractCorporation = $optionData[0]["contractcorporation"];
                $this->maxVolume = $optionData[0]["maxvolume"];
                $this->maxCollateral = $optionData[0]["maxcollateral"];
                $this->blockadeRunnerCutoff = $optionData[0]["blockaderunnercutoff"];
                $this->maxThresholdPrice = $optionData[0]["maxthresholdprice"];
                $this->gatePrice = $optionData[0]["gateprice"];
                //Restrictions
                $this->onlyApprovedRoutes = boolval($optionData[0]["onlyapprovedroutes"]);
                $this->allowHighsecToHighsec = boolval($optionData[0]["allowhighsectohighsec"]);
                $this->allowLowsec = boolval($optionData[0]["allowlowsec"]);
                $this->allowNullsec = boolval($optionData[0]["allownullsec"]);
                $this->allowWormholes = boolval($optionData[0]["allowwormholes"]);
                $this->allowPochven = boolval($optionData[0]["allowpochven"]);
                $this->allowRush = boolval($optionData[0]["allowrush"]);
                //Volume Controls
                $this->highsecToHighsecMaxVolume = $optionData[0]["highsectohighsecmaxvolume"];
                $this->maxWormholeVolume = $optionData[0]["maxwormholevolume"];
                $this->maxPochvenVolume = $optionData[0]["maxpochvenvolume"];
                //Pricing
                $this->rushMultiplier = $optionData[0]["rushmultiplier"];
                $this->nonstandardMultiplier = $optionData[0]["nonstandardmultiplier"];
                $this->wormholePrice = $optionData[0]["wormholeprice"];
                $this->pochvenPrice = $optionData[0]["pochvenprice"];
                $this->collateralPremium = $optionData[0]["collateralpremium"];

                return true;
            }
            else {
                $this->errors[] = "No routing options configured. Please run the initial setup script.";
                return false;
            }

        }
        
    }

?>