<?php

    namespace Ridley\Views\Manager;

    class Templates {
        
        protected function mainTemplate() {

            $this->errorTemplate();
            ?>

            <hr class="text-light">

            <div class="row text-light">
                <div class="col-xl-7">

                    <div class="row">
                        <div class="col-md-4">
                            <h3 class="mt-3">Standard Routes</h3>
                        </div>
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-outline-primary btn-sm mt-4 w-100" data-bs-toggle="modal" data-bs-target="#creation-modal">New Route</button>
                        </div>
                    </div>

                    <table class="table table-dark align-middle text-start text-wrap small mt-4">
                        <thead class="p-4">
                            <tr class="align-middle">
                                <th scope="col" style="width: 15%;">Start</th>
                                <th scope="col" style="width: 15%;">End</th>
                                <th scope="col" style="width: 10%;">Model</th>
                                <th scope="col" style="width: 15%;">Price</th>
                                <th scope="col" style="width: 10%;">Premium</th>
                                <th scope="col" style="width: 12.5%;">Max Volume</th>
                                <th scope="col" style="width: 15%;">Max Collateral</th>
                                <th scope="col" style="width: 7.5%;"></th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php $this->routeLister(); ?>

                        </tbody>
                    </table>

                </div>
                <div class="col-xl-3">

                    <h3 class="mt-3">Range Tiers</h3>

                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->tierLister(); ?>

                    </ul>
                    <form method="post" action="/manager/" class="input-group mt-3 mb-3">
                        <input type="text" name="tier_range" id="tier_range" class="form-control" placeholder="Max Range">
                        <span class="input-group-text">LY</span>
                        <input type="text" name="tier_price" id="tier_price" class="form-control" placeholder="Price">
                        <span class="input-group-text">ISK/m³</span>
                        <button type="submit" name="Action" value="Add_Tier" class="btn btn-primary">+</button>
                    </form>

                </div>
                <div class="col-xl-2">

                    <h3 class="mt-3">Prohibited Regions</h3>

                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->regionRestrictionLister(); ?>

                    </ul>
                    <form method="post" action="/manager/" class="input-group mt-3 mb-3">
                        <input type="text" name="new_region_restriction" id="new_region_restriction" class="form-control" placeholder="Region Name">
                        <button type="submit" name="Action" value="Add_Restricted_Region" class="btn btn-primary">+</button>
                    </form>

                    <h3 style="margin-top: 3rem !important;">Prohibited Systems</h3>

                    <ul class="list-group" style="margin-top: 2rem !important;">

                        <?php $this->systemRestrictionLister(); ?>

                    </ul>
                    <form method="post" action="/manager/" class="input-group mt-3 mb-3">
                        <input type="text" name="new_system_restriction" id="new_system_restriction" class="form-control" placeholder="System Name">
                        <button type="submit" name="Action" value="Add_Restricted_System" class="btn btn-primary">+</button>
                    </form>

                </div>
            </div>

            <?php $this->creationModalTemplate(); ?>

            <hr class="text-light mt-3">
            
            <form class="row text-light mt-3" method="post" action="/manager/">
                <div class="col-xl-3">

                    <div class="h3 mb-0">General Settings</div>

                    <label for="contractCorporation" class="form-label" style="margin-top: 2.25rem !important;">Contract Corporation</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->contractCorporation); ?>" name="contractCorporation" id="contractCorporation" required>

                    <label for="maxVolume" class="form-label mt-3">Max Volume</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->maxVolume); ?>" name="maxVolume" id="maxVolume" required>
                        <span class="input-group-text">m³</span>
                    </div>

                    <label for="maxCollateral" class="form-label mt-3">Max Collateral</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->maxCollateral); ?>" name="maxCollateral" id="maxCollateral" required>
                        <span class="input-group-text">ISK</span>
                    </div>

                    <label for="blockadeRunnerCutoff" class="form-label mt-3">Blockade Runner Cutoff</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->blockadeRunnerCutoff); ?>" name="blockadeRunnerCutoff" id="blockadeRunnerCutoff" required>
                        <span class="input-group-text">m³</span>
                    </div>

                    <label for="maxThresholdPrice" class="form-label mt-3">Max Tier Price</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->maxThresholdPrice); ?>" name="maxThresholdPrice" id="maxThresholdPrice" required>
                        <span class="input-group-text">ISK/m³</span>
                    </div>

                    <label for="gatePrice" class="form-label mt-3">Gate Price</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->gatePrice); ?>" name="gatePrice" id="gatePrice" required>
                        <span class="input-group-text">ISK/m³/Jump</span>
                    </div>

                </div>
                <div class="col-xl-3">

                    <div class="h3 ms-4 mb-0">Restrictions</div>

                    <div class="form-check form-switch ms-4" style="margin-top: 3.25rem !important;">
                        <input class="form-check-input" type="checkbox" role="switch" name="onlyApprovedRoutes" id="onlyApprovedRoutes" value="true" <?php echo $this->controller->onlyApprovedRoutes ? "checked" : ""; ?>>
                        <label class="form-check-label" for="onlyApprovedRoutes">Only Approved Routes</label>
                    </div>
                    <div class="form-check form-switch ms-4" style="margin-top: 1.25rem !important;">
                        <input class="form-check-input" type="checkbox" role="switch" name="allowHighsecToHighsec" id="allowHighsecToHighsec" value="true" <?php echo $this->controller->allowHighsecToHighsec ? "checked" : ""; ?>>
                        <label class="form-check-label" for="allowHighsecToHighsec">Allow Highsec <-> Highsec</label>
                    </div>
                    <div class="form-check form-switch ms-4" style="margin-top: 1.25rem !important;">
                        <input class="form-check-input" type="checkbox" role="switch" name="allowLowsec" id="allowLowsec" value="true" <?php echo $this->controller->allowLowsec ? "checked" : ""; ?>>
                        <label class="form-check-label" for="allowLowsec">Allow Lowsec</label>
                    </div>
                    <div class="form-check form-switch ms-4" style="margin-top: 1.25rem !important;">
                        <input class="form-check-input" type="checkbox" role="switch" name="allowNullsec" id="allowNullsec" value="true" <?php echo $this->controller->allowNullsec ? "checked" : ""; ?>>
                        <label class="form-check-label" for="allowNullsec">Allow Nullsec</label>
                    </div>
                    <div class="form-check form-switch ms-4" style="margin-top: 1.25rem !important;">
                        <input class="form-check-input" type="checkbox" role="switch" name="allowWormholes" id="allowWormholes" value="true" <?php echo $this->controller->allowWormholes ? "checked" : ""; ?>>
                        <label class="form-check-label" for="allowWormholes">Allow Wormholes</label>
                    </div>
                    <div class="form-check form-switch ms-4" style="margin-top: 1.25rem !important;">
                        <input class="form-check-input" type="checkbox" role="switch" name="allowPochven" id="allowPochven" value="true" <?php echo $this->controller->allowPochven ? "checked" : ""; ?>>
                        <label class="form-check-label" for="allowPochven">Allow Pochven</label>
                    </div>
                    <div class="form-check form-switch ms-4" style="margin-top: 1.25rem !important;">
                        <input class="form-check-input" type="checkbox" role="switch" name="allowRush" id="allowRush" value="true" <?php echo $this->controller->allowRush ? "checked" : ""; ?>>
                        <label class="form-check-label" for="allowRush">Allow Rush</label>
                    </div>

                </div>
                <div class="col-xl-3">

                    <div class="h3 mb-0">Specific Volume Limitations</div>

                    <label for="highsecToHighsecMaxVolume" class="form-label" style="margin-top: 2.25rem !important;">Max Highsec <-> Highsec Volume</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->highsecToHighsecMaxVolume); ?>" name="highsecToHighsecMaxVolume" id="highsecToHighsecMaxVolume" required>
                        <span class="input-group-text">m³</span>
                    </div>

                    <label for="maxWormholeVolume" class="form-label mt-3">Max Wormhole Volume</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->maxWormholeVolume); ?>" name="maxWormholeVolume" id="maxWormholeVolume" required>
                        <span class="input-group-text">m³</span>
                    </div>

                    <label for="maxPochvenVolume" class="form-label mt-3">Max Pochven Volume</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->maxPochvenVolume); ?>" name="maxPochvenVolume" id="maxPochvenVolume" required>
                        <span class="input-group-text">m³</span>
                    </div>

                </div>
                <div class="col-xl-3">

                    <div class="h3 mb-0">Specific Pricing</div>

                    <label for="rushMultiplier" class="form-label" style="margin-top: 2.25rem !important;">Rush Multiplier</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->rushMultiplier); ?>" name="rushMultiplier" id="rushMultiplier" required>
                        <span class="input-group-text">×</span>
                    </div>

                    <label for="nonstandardMultiplier" class="form-label mt-3">Non-Standard Route Multiplier</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->nonstandardMultiplier); ?>" name="nonstandardMultiplier" id="nonstandardMultiplier" required>
                        <span class="input-group-text">×</span>
                    </div>

                    <label for="wormholePrice" class="form-label mt-3">Wormhole Price</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->wormholePrice); ?>" name="wormholePrice" id="wormholePrice" required>
                        <span class="input-group-text">ISK/m³</span>
                    </div>

                    <label for="pochvenPrice" class="form-label mt-3">Pochven Price</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->pochvenPrice); ?>" name="pochvenPrice" id="pochvenPrice" required>
                        <span class="input-group-text">ISK/m³</span>
                    </div>

                    <label for="collateralPremium" class="form-label mt-3">Collateral Premium</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($this->controller->collateralPremium); ?>" name="collateralPremium" id="collateralPremium" required>
                        <span class="input-group-text">%</span>
                    </div>

                    <button type="submit" name="Action" value="Update_Settings" class="btn btn-primary w-100 mt-4">Update Settings</button>

                </div>
            </form>
            
            <?php
        }

        protected function errorTemplate() {
            
            foreach ($this->controller->errors as $eachError) {
            ?>

                <div class="alert alert-danger d-flex align-items-center mt-3" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <div><?php echo htmlspecialchars($eachError); ?></div>
                </div>

            <?php
            }
            
        }

        protected function tierLister() {

            foreach ($this->model->tiers as $eachTier) {
            ?>

                <li class="list-group-item bg-dark text-light">
                    <div class="row">
                        <div class="col-xl-5 pt-2 pb-1">
                            <?php echo "<" . htmlspecialchars($eachTier["threshold"]) . " LY"; ?>
                        </div>
                        <div class="col-xl-5 pt-2 pb-1">
                            <?php echo htmlspecialchars($eachTier["price"]) . " ISK/m³"; ?>
                        </div>
                        <div class="col-xl-2 d-flex justify-content-end pe-1">
                            <form method="post" action="/manager/">
                                <input type="hidden" name="old_tier_range" value="<?php echo htmlspecialchars($eachTier["threshold"]); ?>"> 
                                <button type="submit" name="Action" value="Remove_Tier" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </li>
                
            <?php
            }

        }

        protected function regionRestrictionLister() {

            foreach ($this->model->regionRestrictions as $eachRestriction) {
            ?>

                <li class="list-group-item bg-dark text-light">
                    <div class="row">
                        <div class="col-xl-9 pt-2 pb-1">
                            <?php echo htmlspecialchars($eachRestriction); ?>
                        </div>
                        <div class="col-xl-3 d-flex justify-content-end pe-1">
                            <form method="post" action="/manager/">
                                <input type="hidden" name="old_region_restriction" value="<?php echo htmlspecialchars($eachRestriction); ?>"> 
                                <button type="submit" name="Action" value="Remove_Restricted_Region" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </li>
                
            <?php
            }

        }

        protected function systemRestrictionLister() {

            foreach ($this->model->systemRestrictions as $eachRestriction) {
            ?>

                <li class="list-group-item bg-dark text-light">
                    <div class="row">
                        <div class="col-xl-9 pt-2 pb-1">
                            <?php echo htmlspecialchars($eachRestriction); ?>
                        </div>
                        <div class="col-xl-3 d-flex justify-content-end pe-1">
                            <form method="post" action="/manager/">
                                <input type="hidden" name="old_system_restriction" value="<?php echo htmlspecialchars($eachRestriction); ?>"> 
                                <button type="submit" name="Action" value="Remove_Restricted_System" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </li>

            <?php
            }

        }

        protected function routeLister() {

            foreach ($this->model->routes as $eachRoute) {
            ?>

            <tr>
                <td><?php echo htmlspecialchars($eachRoute["start"]); ?></td>
                <td><?php echo htmlspecialchars($eachRoute["end"]); ?></td>
                <td><?php echo htmlspecialchars($eachRoute["model"]); ?></td>
                <td><?php echo isset($eachRoute["price"]) ? htmlspecialchars(number_format($eachRoute["price"])) . " ISK" : ""; ?></td>
                <td><?php echo isset($eachRoute["premium"]) ? htmlspecialchars($eachRoute["premium"]) . " %" : ""; ?></td>
                <td><?php echo isset($eachRoute["maxvolume"]) ? htmlspecialchars(number_format($eachRoute["maxvolume"])) . " m³" : ""; ?></td>
                <td><?php echo isset($eachRoute["maxcollateral"]) ? htmlspecialchars(number_format($eachRoute["maxcollateral"])) . " ISK" : ""; ?></td>
                <td class="text-end">
                    <form method="post" action="/manager/">
                        <input type="hidden" name="old_route_origin" value="<?php echo htmlspecialchars($eachRoute["start"]); ?>"> 
                        <input type="hidden" name="old_route_destination" value="<?php echo htmlspecialchars($eachRoute["end"]); ?>">
                        <button type="submit" name="Action" value="Remove_Route" class="btn btn-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
                
            <?php
            }

        }

        protected function creationModalTemplate() {
        ?>
            <div id="creation-modal" class="modal fade" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content bg-dark text-light border-secondary">
                        <div class="modal-header border-secondary">

                            <h5 class="modal-title">Create a New Route</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                        </div>
                        <form class="modal-body" method="post" action="/manager/">

                            <div class="alert alert-primary d-flex align-items-center mt-3" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <div>Using Range Only pricing for Pochven or Wormhole routes will produce unintuitive and possibly extremely high prices. Gate Only pricing will be overridden on Pochven and Wormhole routes.</div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-xl-3">

                                    <label for="route_origin" class="form-label h5">Start</label>
                                    <input type="text" name="route_origin" id="route_origin" class="form-control" required>

                                </div>
                                <div class="col-xl-3">

                                    <label for="route_destination" class="form-label h5">End</label>
                                    <input type="text" name="route_destination" id="route_destination" class="form-control" required>

                                </div>
                                <div class="col-xl-3">

                                    <h5 class="mb-2 ms-4">Price Model</h5>

                                    <div class="form-check ms-4">
                                        <input class="form-check-input" type="radio" name="route_price_model" id="route_price_model" value="Standard" checked>
                                        <label class="form-check-label" for="route_price_model">Standard</label>
                                    </div>
                                    <div class="form-check ms-4">
                                        <input class="form-check-input" type="radio" name="route_price_model" id="route_price_model" value="Fixed">
                                        <label class="form-check-label" for="route_price_model">Fixed</label>
                                    </div>
                                    <div class="form-check ms-4">
                                        <input class="form-check-input" type="radio" name="route_price_model" id="route_price_model" value="Range">
                                        <label class="form-check-label" for="route_price_model">Range Only</label>
                                    </div>
                                    <div class="form-check ms-4">
                                        <input class="form-check-input" type="radio" name="route_price_model" id="route_price_model" value="Gate">
                                        <label class="form-check-label" for="route_price_model">Gate Only</label>
                                    </div>

                                    <div class="form-check form-switch mt-3 ms-4">
                                        <input class="form-check-input" type="checkbox" role="switch" name="route_add_inverse" id="route_add_inverse" value="true">
                                        <label class="form-check-label" for="route_add_inverse">Also Add Inverse Route</label>
                                    </div>

                                </div>
                                <div class="col-xl-3">

                                    <label for="route_price" class="form-label h5">Price Override</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="route_price" id="route_price">
                                        <span class="input-group-text">ISK</span>
                                    </div>

                                    <label for="route_premium" class="form-label h5 mt-3">Collateral Premium Override</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="route_premium" id="route_premium">
                                        <span class="input-group-text">%</span>
                                    </div>

                                    <label for="route_max_volume" class="form-label h5 mt-3">Max Volume Override</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="route_max_volume" id="route_max_volume">
                                        <span class="input-group-text">m³</span>
                                    </div>

                                    <label for="route_max_collateral" class="form-label h5 mt-3">Max Collateral Override</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="route_max_collateral" id="route_max_collateral">
                                        <span class="input-group-text">ISK</span>
                                    </div>

                                </div>

                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" name="Action" value="Add_Route" class="btn btn-primary w-100">Create</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

        <?php
        }
        
        protected function metaTemplate() {
            ?>
            
            <title>Route Manager</title>

            <script src="/resources/js/Manager.js"></script>
            
            <?php
        }
        
    }

    class View extends Templates implements \Ridley\Interfaces\View {

        protected $model;
        protected $controller;
        
        public function __construct(
            private \Ridley\Core\Dependencies\DependencyManager $dependencies
        ) {
            $this->model = $this->dependencies->get("Model");
            $this->controller = $this->dependencies->get("Controller");
        }
        
        public function renderContent() {
            
            $this->mainTemplate();
            
        }
        
        public function renderMeta() {
            
            $this->metaTemplate();
            
        }
        
    }

?>