<?php

    namespace Ridley\Apis\Home;

    class Api implements \Ridley\Interfaces\Api {

        private $databaseConnection;

        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {

            $this->databaseConnection = $this->dependencies->get("Database");

            if (isset($_POST["Action"])) {

                if ($_POST["Action"] == "Get_Systems") {
                    $this->getSystems();
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

        private function getSystems() {

            $optionQuery = $this->databaseConnection->prepare("SELECT * FROM options ORDER BY iteration DESC LIMIT 1");
            $optionQuery->execute();
            $optionData = $optionQuery->fetchAll(\PDO::FETCH_ASSOC);

            if (!empty($optionData)) {

                if (boolval($optionData[0]["onlyapprovedroutes"])) {

                    $queryString = "SELECT name FROM evesystems WHERE
                        id IN (
                            SELECT DISTINCT start FROM routes
                            UNION
                            SELECT DISTINCT end FROM routes
                        )";

                }
                else {

                    $allowLowsec = boolval($optionData[0]["allowlowsec"]);
                    $allowNullsec = boolval($optionData[0]["allownullsec"]);
                    $allowWormholes = boolval($optionData[0]["allowwormholes"]);
                    $allowPochven = boolval($optionData[0]["allowpochven"]);

                    $allowedClassList = ["'Highsec'"];

                    if ($allowLowsec) {
                        $allowedClassList[] = "'Lowsec'";
                    }
                    if ($allowNullsec) {
                        $allowedClassList[] = "'Nullsec'";
                    }
                    if ($allowWormholes) {
                        $allowedClassList[] = "'Wormhole'";
                    }
                    if ($allowPochven) {
                        $allowedClassList[] = "'Pochven'";
                    }

                    $allowedClasses = implode(", ", $allowedClassList);

                    $queryString = "SELECT name FROM evesystems WHERE 
                        class IN ($allowedClasses) 
                        AND id NOT IN (SELECT id FROM restrictedlocations WHERE type = 'System') 
                        AND regionid NOT IN (SELECT id FROM restrictedlocations WHERE type = 'Region')";

                }

                $systemQuery = $this->databaseConnection->prepare($queryString);
                $systemQuery->execute();
    
                echo json_encode($systemQuery->fetchAll(\PDO::FETCH_COLUMN, 0));

            }

        }

    }

?>
