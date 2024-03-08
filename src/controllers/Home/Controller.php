<?php

    namespace Ridley\Controllers\Home;

    class Controller implements \Ridley\Interfaces\Controller {

        private $databaseConnection;
        private $esiHandler;
        public $errors = [];

        //General Settings
        public $contractCorporation;
        private $maxVolume;
        private $maxCollateral;
        private $blockadeRunnerCutoff;
        private $maxThresholdPrice;
        private $gatePrice;
        //Restrictions
        private $onlyApprovedRoutes;
        private $allowHighsecToHighsec;
        private $allowLowsec;
        private $allowNullsec;
        private $allowWormholes;
        private $allowPochven;
        public $allowRush;
        //Volume Controls
        private $highsecToHighsecMaxVolume;
        private $maxWormholeVolume;
        private $maxPochvenVolume;
        //Pricing
        private $rushMultiplier;
        private $nonstandardMultiplier;
        private $wormholePrice;
        private $pochvenPrice;
        private $collateralPremium;

        public $quoteProcessed = false;
        public $priceModel;
        public $multipliers = [];
        public $unitPriceString;
        public $collateralPremiumString;
        public $volumeString;
        public $destinationString;
        public $collateralString;
        public $priceString;
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {

            $this->databaseConnection = $this->dependencies->get("Database");
            $this->esiHandler =  new \Ridley\Objects\ESI\Handler($this->databaseConnection);
            
            if ($this->loadOptions()) {

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

                        $this->getQuote(
                            $_POST["origin"], 
                            $_POST["destination"], 
                            (int)$_POST["collateral"], 
                            (int)$_POST["volume"],
                            isset($_POST["rush"])
                        );

                    }
                    else {

                        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                        $this->errors[] = "Failed to Process Quote! Arguments are either missing or not in a valid format.";

                    }

                }

            }
            
        }

        private function getQuote($origin, $destination, $collateral, $volume, $rush) {

            $originData = $this->getSystemData($origin);
            $destinationData = $this->getSystemData($destination);

            if (!$this->allowHighsecToHighsec and $originData["class"] == "Highsec" and $destinationData["class"] == "Highsec") {

                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                $this->errors[] = "Failed to Process Quote! Highsec <-> Highsec routes are not permitted.";
                return;

            }

            if (isset($originData, $destinationData)) {

                $routeQuery = $this->databaseConnection->prepare(
                    "SELECT 
                        basepriceoverride, 
                        pricemodel, 
                        collateralpremiumoverride, 
                        maxvolumeoverride, 
                        maxcollateraloverride 
                    FROM routes 
                    WHERE start = :start AND end = :end"
                );
                $routeQuery->bindParam(":start", $originData["id"]);
                $routeQuery->bindParam(":end", $destinationData["id"]);
                $routeQuery->execute();

                $routeData = $routeQuery->fetch(\PDO::FETCH_ASSOC);

                if (!$this->checkVolume($originData, $destinationData, $routeData, $volume)) {

                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Failed to Process Quote! Max volume exceeded for your selected route or system combination.";
                    return;

                }

                if (!$this->checkCollateral($routeData, $collateral)) {

                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Failed to Process Quote! Max collateral exceeded.";
                    return;

                }

                $this->destinationString = $destinationData["name"];
                $this->volumeString = number_format($volume) . " m³";
                $this->collateralString = number_format($collateral) . " ISK";

                //Route Calculation
                if ($routeData !== false) {

                    if ($routeData["pricemodel"] == "Standard") {
                        $this->priceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData);
                    }
                    elseif ($routeData["pricemodel"] == "Fixed") {
                        $this->fixedPriceCheck($collateral, $rush, $routeData);
                    }
                    elseif ($routeData["pricemodel"] == "Range") {
                        $this->rangePriceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData);
                    }
                    elseif ($routeData["pricemodel"] == "Gate") {

                        if ($originData["class"] == "Wormhole" or $destinationData["class"] == "Wormhole") {
                            $this->wormholePriceCheck($collateral, $volume, $rush, $routeData);
                        }
                        elseif ($originData["class"] == "Pochven" or $destinationData["class"] == "Pochven") {
                            $this->pochvenPriceCheck($collateral, $volume, $rush, $routeData);
                        }
                        else {
                            $this->gatePriceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData);
                        }

                    }
                    else {
                        header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                        throw new \Exception("Route uses an invalid model.", 12001);
                        return;
                    }

                }
                elseif ($this->onlyApprovedRoutes) {

                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "Failed to Process Quote! System is not an approved route.";
                    return;

                }
                //Non-Route Calculation
                else {

                    $this->priceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData);

                }

            }

        }

        private function priceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData) {

            if ($originData["class"] == "Wormhole" or $destinationData["class"] == "Wormhole") {
                $this->wormholePriceCheck($collateral, $volume, $rush, $routeData);
            }
            elseif ($originData["class"] == "Pochven" or $destinationData["class"] == "Pochven") {
                $this->pochvenPriceCheck($collateral, $volume, $rush, $routeData);
            }
            elseif ($volume <= $this->blockadeRunnerCutoff or ($originData["class"] == "Highsec" and $destinationData["class"] == "Highsec")) {
                $this->gatePriceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData);
            }
            else {
                $this->rangePriceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData);
            }

        }

        private function rangePriceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData) {
            $coordinateDistance = (($destinationData["x"] - $originData["x"])**2 + ($destinationData["y"] - $originData["y"])**2 + ($destinationData["z"] - $originData["z"])**2)**(1/2);
            $distance = $coordinateDistance / 9460000000000000;
            $distanceString = number_format($distance, 2);

            $this->priceModel = "Range - $distanceString LY";

            $tierQuery = $this->databaseConnection->prepare("SELECT price FROM tiers WHERE threshold >= :distance ORDER BY threshold ASC LIMIT 1");
            $tierQuery->bindParam(":distance", $distance);
            $tierQuery->execute();
            
            $tierPrice = $tierQuery->fetchColumn();

            $standardPrice = ($tierPrice !== false) ? $tierPrice : $this->maxThresholdPrice;
            $this->unitPriceString = number_format($standardPrice) . " ISK/m³";
            $basePrice = ($routeData["basepriceoverride"] ?? $standardPrice) * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $collateral, $routeData);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $rush, $routeData);

            $this->priceString = number_format($specialAdjustedPrice) . " ISK";
            $this->quoteProcessed = true;

        }

        private function gatePriceCheck($originData, $destinationData, $collateral, $volume, $rush, $routeData) {

            $routeCall = $this->esiHandler->call(
                endpoint: "/route/{origin}/{destination}/",
                origin: $originData["id"],
                destination: $destinationData["id"],
                retries: 1
            );

            if ($routeCall["Success"]) {

                if (!empty($routeCall["Data"])) {

                    $jumps = count($routeCall["Data"]);

                }
                else {

                    header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                    $this->errors[] = "Error! Gate price check attempted on route with no gate connection!";
                    return;

                }

            }
            else {

                header($_SERVER["SERVER_PROTOCOL"] . " 500 Internal Server Error");
                $this->errors[] = "Error! Gate price check attempted on route with no gate connection!";
                return;

            }

            $this->priceModel = "Gate - $jumps Jumps";

            $this->unitPriceString = number_format(($routeData["basepriceoverride"] ?? $this->gatePrice)) . " ISK/Jump/m³";
            $basePrice = ($routeData["basepriceoverride"] ?? $this->gatePrice) * $jumps * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $collateral, $routeData);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $rush, $routeData);

            $this->priceString = number_format($specialAdjustedPrice) . " ISK";
            $this->quoteProcessed = true;

        }

        private function wormholePriceCheck($collateral, $volume, $rush, $routeData) {

            $this->priceModel = "Wormhole";
            $this->unitPriceString = number_format(($routeData["basepriceoverride"] ?? $this->wormholePrice)) . " ISK/m³";
            $basePrice = ($routeData["basepriceoverride"] ?? $this->wormholePrice) * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $collateral, $routeData);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $rush, $routeData);

            $this->priceString = number_format($specialAdjustedPrice) . " ISK";
            $this->quoteProcessed = true;

        }

        private function pochvenPriceCheck($collateral, $volume, $rush, $routeData) {

            $this->priceModel = "Pochven";
            $this->unitPriceString = number_format(($routeData["basepriceoverride"] ?? $this->pochvenPrice)) . " ISK/m³";
            $basePrice = ($routeData["basepriceoverride"] ?? $this->pochvenPrice) * $volume;
            $adjustedPrice = $this->adjustForCollateral($basePrice, $collateral, $routeData);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $rush, $routeData);

            $this->priceString = number_format($specialAdjustedPrice) . " ISK";
            $this->quoteProcessed = true;

        }

        private function fixedPriceCheck($collateral, $rush, $routeData) {

            $this->priceModel = "Fixed";
            $basePrice = $routeData["basepriceoverride"];
            $this->unitPriceString = number_format($basePrice) . " ISK";
            $adjustedPrice = $this->adjustForCollateral($basePrice, $collateral, $routeData);
            $specialAdjustedPrice = $this->adjustForSpecialMultipliers($adjustedPrice, $rush, $routeData);

            $this->priceString = number_format($specialAdjustedPrice) . " ISK";
            $this->quoteProcessed = true;

        }

        private function adjustForSpecialMultipliers($adjustedPrice, $rush, $routeData) {

            if ($rush and $this->allowRush) {
                $this->multipliers["Rush"] = number_format($this->rushMultiplier, 4) . "×";
            }
            if ($routeData === false) {
                $this->multipliers["Non-Standard"] = number_format($this->nonstandardMultiplier, 4) . "×";
            }

            return $adjustedPrice * (($rush and $this->allowRush) ? $this->rushMultiplier : 1) * (($routeData === false) ? $this->nonstandardMultiplier : 1);

        }

        private function adjustForCollateral($basePrice, $collateral, $routeData) {

            $percentage = $routeData["collateralpremiumoverride"] ?? $this->collateralPremium;
            $premiumMultiplier = $percentage / 100;

            $this->collateralPremiumString = number_format(($collateral * $premiumMultiplier)) . " ISK";

            return $basePrice + ($collateral * $premiumMultiplier);

        }

        private function checkCollateral($routeData, $collateral) {

            if (isset($routeData["maxcollateraloverride"])) {
                return $collateral <= $routeData["maxcollateraloverride"];
            }
            else {
                return $collateral <= $this->maxCollateral;
            }

        }

        private function checkVolume($originData, $destinationData, $routeData, $volume) {

            if (isset($routeData["maxvolumeoverride"])) {
                return $volume <= $routeData["maxvolumeoverride"];
            }
            elseif ($originData["class"] == "Highsec" and $destinationData["class"] == "Highsec") {
                return $volume <= $this->highsecToHighsecMaxVolume;
            }
            elseif ($originData["class"] == "Wormhole" or $destinationData["class"] == "Wormhole") {
                return $volume <= $this->maxWormholeVolume;
            }
            elseif ($originData["class"] == "Pochven" or $destinationData["class"] == "Pochven") {
                return $volume <= $this->maxPochvenVolume;
            }
            else {
                return $volume <= $this->maxVolume;
            }

        }

        private function getSystemData($name) {

            if ($this->onlyApprovedRoutes) {

                $queryString = "SELECT id, name, class, x, y, z FROM evesystems WHERE
                    id IN (
                        SELECT DISTINCT start FROM routes
                        UNION
                        SELECT DISTINCT end FROM routes
                    )
                    AND name = :name 
                LIMIT 1";

            }
            else {

                $allowedClassList = ["'Highsec'"];

                if ($this->allowLowsec) {
                    $allowedClassList[] = "'Lowsec'";
                }
                if ($this->allowNullsec) {
                    $allowedClassList[] = "'Nullsec'";
                }
                if ($this->allowWormholes) {
                    $allowedClassList[] = "'Wormhole'";
                }
                if ($this->allowPochven) {
                    $allowedClassList[] = "'Pochven'";
                }

                $allowedClasses = implode(", ", $allowedClassList);

                $queryString = "SELECT id, name, class, x, y, z FROM evesystems WHERE 
                    class IN ($allowedClasses) 
                    AND id NOT IN (SELECT id FROM restrictedlocations WHERE type = 'System') 
                    AND regionid NOT IN (SELECT id FROM restrictedlocations WHERE type = 'Region')
                    AND name = :name 
                LIMIT 1";

            }

            try {
                $systemQuery = $this->databaseConnection->prepare($queryString);
                $systemQuery->bindParam(":name", $name);
                $systemQuery->execute();

                $result = $systemQuery->fetch(\PDO::FETCH_ASSOC);

                if ($result !== false) {
                    return $result;
                }
                else {
                    header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                    $this->errors[] = "The System $name Was Not Found! It either does not exist, or is not approved for use.";
                    return null;
                }

            }
            catch (\Exception $error) {
                header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request");
                $this->errors[] = "The System $name Was Not Found! " . $error->getMessage();
                return null;
            }

        }

        private function loadOptions() {
            
            $optionQuery = $this->databaseConnection->prepare("SELECT * FROM options ORDER BY iteration DESC LIMIT 1");
            $optionQuery->execute();
            $optionData = $optionQuery->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($optionData)) {

                //General Settings
                $this->contractCorporation = $optionData[0]["contractcorporation"];
                $this->maxVolume = (int)$optionData[0]["maxvolume"];
                $this->maxCollateral = (int)$optionData[0]["maxcollateral"];
                $this->blockadeRunnerCutoff = (int)$optionData[0]["blockaderunnercutoff"];
                $this->maxThresholdPrice = (int)$optionData[0]["maxthresholdprice"];
                $this->gatePrice = (int)$optionData[0]["gateprice"];
                //Restrictions
                $this->onlyApprovedRoutes = boolval($optionData[0]["onlyapprovedroutes"]);
                $this->allowHighsecToHighsec = boolval($optionData[0]["allowhighsectohighsec"]);
                $this->allowLowsec = boolval($optionData[0]["allowlowsec"]);
                $this->allowNullsec = boolval($optionData[0]["allownullsec"]);
                $this->allowWormholes = boolval($optionData[0]["allowwormholes"]);
                $this->allowPochven = boolval($optionData[0]["allowpochven"]);
                $this->allowRush = boolval($optionData[0]["allowrush"]);
                //Volume Controls
                $this->highsecToHighsecMaxVolume = (int)$optionData[0]["highsectohighsecmaxvolume"];
                $this->maxWormholeVolume = (int)$optionData[0]["maxwormholevolume"];
                $this->maxPochvenVolume = (int)$optionData[0]["maxpochvenvolume"];
                //Pricing
                $this->rushMultiplier = (float)$optionData[0]["rushmultiplier"];
                $this->nonstandardMultiplier = (float)$optionData[0]["nonstandardmultiplier"];
                $this->wormholePrice = (int)$optionData[0]["wormholeprice"];
                $this->pochvenPrice = (int)$optionData[0]["pochvenprice"];
                $this->collateralPremium = (float)$optionData[0]["collateralpremium"];

                return true;
            }
            else {
                $this->errors[] = "No routing options configured. Please run the initial setup script.";
                return false;
            }

        }
        
    }

?>