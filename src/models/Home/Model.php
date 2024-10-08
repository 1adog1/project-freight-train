<?php

    namespace Ridley\Models\Home;

    class Model implements \Ridley\Interfaces\Model {
        
        private $controller;
        private $databaseConnection;
        
        public $tiers = [];
        public $routes = [];

        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            
            $this->controller = $this->dependencies->get("Controller");
            $this->databaseConnection = $this->dependencies->get("Database");

            $this->tiers = $this->loadTiers();
            $this->loadRoutes();

        }

        private function loadTiers() {

            $tierQuery = $this->databaseConnection->prepare("SELECT threshold, price FROM tiers ORDER BY threshold DESC");
            $tierQuery->execute();

            return (array)$tierQuery->fetchAll(\PDO::FETCH_ASSOC);

        }

        private function loadRoutes() {

            $routeQuery = $this->databaseConnection->prepare(
                "SELECT 
                    startsystem.name AS start, 
                    endsystem.name AS end,
                    routes.pricemodel AS model,
                    routes.basepriceoverride AS baseprice,
                    routes.gatepriceoverride AS gateprice,
                    routes.minimumpriceoverride AS minimumprice,
                    routes.collateralpremiumoverride AS premium,
                    routes.maxvolumeoverride AS maxvolume,
                    routes.maxcollateraloverride AS maxcollateral,
                    routes.disablehighcollateral AS disablehighcollateral
                FROM routes 
                INNER JOIN evesystems AS startsystem ON routes.start = startsystem.id 
                INNER JOIN evesystems AS endsystem ON routes.end = endsystem.id
                ORDER BY start ASC, end ASC"
            );
            $routeQuery->execute();

            $routeData = $routeQuery->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($routeData as $eachRoute) {
                $thisRoute = [];
                $thisRoute["Start"] = $eachRoute["start"];
                $thisRoute["End"] = $eachRoute["end"];
                $thisRoute["Model"] = $eachRoute["model"];
                $thisRoute["Overrides"] = [];

                if (isset($eachRoute["baseprice"])) {
                    $thisRoute["Overrides"][] = htmlspecialchars("Base Price: " . number_format((int)$eachRoute["baseprice"]) . " ISK(/m³)");
                }
                if (isset($eachRoute["gateprice"])) {
                    $thisRoute["Overrides"][] = htmlspecialchars("Gate Price: " . number_format((int)$eachRoute["gateprice"]) . " ISK/Jump/m³");
                }
                if (isset($eachRoute["minimumprice"])) {
                    $thisRoute["Overrides"][] = htmlspecialchars("Minimum Price: " . number_format((int)$eachRoute["minimumprice"]) . " ISK");
                }
                if (isset($eachRoute["premium"])) {
                    $thisRoute["Overrides"][] = htmlspecialchars("Collateral Premium: " . $eachRoute["premium"] . " %");
                }
                if (isset($eachRoute["maxvolume"])) {
                    $thisRoute["Overrides"][] = htmlspecialchars("Max Volume: " . number_format((int)$eachRoute["maxvolume"]) . " m³");
                }
                if (isset($eachRoute["maxcollateral"])) {
                    $thisRoute["Overrides"][] = htmlspecialchars("Max Collateral: " . number_format((int)$eachRoute["maxcollateral"]) . " ISK");
                }
                if (boolval($eachRoute["disablehighcollateral"])) {
                    $thisRoute["Overrides"][] = htmlspecialchars("High Collateral Premium Disabled");
                }

                $this->routes[] = $thisRoute;
            }

        }
        
    }

?>