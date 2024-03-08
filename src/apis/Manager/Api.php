<?php

    namespace Ridley\Apis\Manager;

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
                elseif ($_POST["Action"] == "Get_Regions") {
                    $this->getRegions();
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

            $systemQuery = $this->databaseConnection->prepare("SELECT name FROM evesystems");
            $systemQuery->execute();

            echo json_encode($systemQuery->fetchAll(\PDO::FETCH_COLUMN, 0));

        }

        private function getRegions() {

            $regionQuery = $this->databaseConnection->prepare("SELECT DISTINCT regionname FROM evesystems");
            $regionQuery->execute();

            echo json_encode($regionQuery->fetchAll(\PDO::FETCH_COLUMN, 0));

        }

    }

?>
