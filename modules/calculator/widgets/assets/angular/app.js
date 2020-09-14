(function (angular, $) {
	var path = location.pathname.substring(1);
	var isBackend = path.substring(0, 7);

	if(isBackend === 'backend'){
		var module_adaptor = angular.module('backend');
	} else {
		var module_adaptor = angular.module('app');
	}


	module_adaptor
		.controller('CalculatorController', ['$q', '$scope', 'PriceCalculator', 'DealerinventoryCar',
			function ($q, $scope, PriceCalculator, DealerinventoryCar) {

				$scope.widgetPreloader = {
					widgetSuccessfullyLoaded: false,
				};
				$scope.decorators = {
					config: {
						INVOICE_DISCOUNT: 'invertDecorator',
						TAX_PERCENTAGE: 'percentageDecorator'
					},
					init: function (fieldName, action, objectValues) {
						if($scope.decorators.config.hasOwnProperty(fieldName)){
							var decoratorName = $scope.decorators.config[fieldName];
							var methodName = action;
							return $scope.decorators[decoratorName][methodName](objectValues);
						} else {
							return objectValues.value;
						}
					},
					processIn: function(){
						var result = $scope.fields;
						Object.keys(result).forEach(function (ind){
							Object.keys(result[ind]).forEach(function (property){
								if(result[ind][property].hasOwnProperty('value')){
									$scope.fields[ind][property].value = $scope.decorators.init(property, 'in', {value: result[ind][property].value});
								}
							});
						});
					},
					processOut: function(){
						var result = $scope.fields;
						Object.keys(result).forEach(function (ind){
							Object.keys(result[ind]).forEach(function (property){
								if(result[ind][property].hasOwnProperty('value')){
									$scope.fields[ind][property].value = $scope.decorators.init(property, 'out', {value: result[ind][property].value});
								}
							});
						});
					},
					invertDecorator: {
						in: function (objectValues) {
							return objectValues.value > 0 ? objectValues.value * -1 : '+'+(objectValues.value * -1);
						},
						out: function (objectValues) {
							var value = parseFloat(objectValues.value);
							return value * -1;
						}
					},
					percentageDecorator: {
						in: function (objectValues) {
							var value = objectValues.value;
							return value * 100+'%';
						},
						out: function (objectValues) {
							var value = objectValues.value.substr(0, objectValues.value.length-1);
							return value / 100;
						}
					}
				};
				$scope.fields = {

				};

				$scope.liverequestUsedRates = {
					collection: [

					],
					load: function(){
						PriceCalculator.loadLiverequestUsedRates($scope.additionalConfig.liverequest_id).then(function (data) {
							data.data.forEach(function(value, key, map){
								$scope.liverequestUsedRates.collection.push(value);
							});

						}).catch(function (response) {
							console.log(response);
						});
					},
					add: function () {
						this.collection.push({
							month_from: 0,
							month_to: 0,
							value: 0,
							isDeleted: false
						});
					},
					delete: function (index) {
						this.collection[index].isDeleted = true;
						$scope.calculate();
					},
					buildProviderKeys: function () {
						Object.keys($scope.fields).forEach(function (ind){

							$scope.liverequestUsedRates.collection.forEach(function(value, key, map){
								if($scope.fields[ind]['MONTHLY_TERM'].value >= value.monthFrom && $scope.fields[ind]['MONTHLY_TERM'].value <= value.monthTo){
									$scope.fields[ind]['FINANCERATE']['value'] = value.value;
								}
							});
						});
					}
				};

				$scope.liverequestFees = {
					collection: [

					],
					load: function(){
						PriceCalculator.loadLiverequestFees($scope.additionalConfig.liverequest_id).then(function (data) {
							data.data.forEach(function(value, key, map){
								$scope.liverequestFees.collection.push(value);
							});

							$scope.liverequestFees.buildProviderKeys();
						}).catch(function (response) {
							console.log(response);
						});
					},
					add: function () {
						this.collection.push({
							name: 'Custom Fee Name',
							value: 0,
							type: 2,
							is_taxable: false,
							isNew: true,
							isDeleted: false,
						});
					},
					delete: function (index) {
						this.collection[index].isDeleted = true;
						$scope.calculate();
					},
					buildProviderKeys: function () {
						Object.keys($scope.fields).forEach(function (ind){
							$scope.fields[ind]['TAXABLE_COMMON_FEES'] = {disabled: false, value: 0};
							$scope.fields[ind]['NONTAXABLE_COMMON_FEES'] = {disabled: false, value: 0};
							$scope.fields[ind]['TAXABLE_CUSTOM_FEES'] = {disabled: false, value: 0};
							$scope.fields[ind]['NONTAXABLE_CUSTOM_FEES'] = {disabled: false, value: 0};

							$scope.liverequestFees.collection.forEach(function(value, key, map){

								if(value.type === 0){
									$scope.fields[ind]['CARVOY_FEE'] = {
										disabled: false,
										value: value.value
									};
								} else if(value.type === 3){
									$scope.fields[ind]['BANK_FEE'] = {
										disabled: false,
										value: value.value
									};
								}
								if(!value.isDeleted){
									if(value.type === 1 && value.is_taxable === true){
										$scope.fields[ind]['TAXABLE_COMMON_FEES']['value'] += parseFloat(value.value);
									} else if(value.type === 1 && value.is_taxable === false){
										$scope.fields[ind]['NONTAXABLE_COMMON_FEES']['value'] += parseFloat(value.value);
									} else if(value.type === 2 && value.is_taxable === true){
										$scope.fields[ind]['TAXABLE_CUSTOM_FEES']['value'] += parseFloat(value.value);
									} else if(value.type === 2 && value.is_taxable === false){
										$scope.fields[ind]['NONTAXABLE_CUSTOM_FEES']['value'] += parseFloat(value.value);
									}
								}


							});
						});
					}
				};

				$scope.editMode = 1;
				$scope.isManualMode = false;
				$scope.isIgniteMode = false;
				$scope.isLiverequestMode = false;

				$scope.isLeaseLiverequest = function(){

				};

				$scope.isFinanceLiverequest = function(){

				};

				$scope.addons = {
					rebates: {
						allCustomRebatesList: [],
						selected: [],
						initCustomRebates: function (term) {
							this.allCustomRebatesList = [];
							this.selected = [];
							PriceCalculator.loadCustomRebatesForLiverequest($scope.additionalConfig.liverequest_id, term).then(function (data) {
								
								if(!Array.isArray(data.data)){
									//Show all rebates in multiselect
									$scope.addons.rebates.allCustomRebatesList = data.data.available;
									Object.keys(data.data.selected).forEach(function (key){
										$scope.addons.rebates.selected.push(data.data.selected[key]);
									});
								}
							}).catch(function (response) {
								console.log(response);
							});
						},
						buildProviderKeys: function () {
							Object.keys($scope.fields).forEach(function (ind){
								$scope.fields[ind]['AUTO_REBATES'] = {disabled: false, value: 0};
								$scope.fields[ind]['CUSTOM_REBATES'] = {disabled: false, value: 0};
								$scope.fields[ind]['AUTO_DEALERCASH'] = {disabled: false, value: 0};
								$scope.fields[ind]['CUSTOM_DEALERCASH'] = {disabled: false, value: 0};
								$scope.fields[ind]['custom_rebate_signature_ids'] = {disabled: false, value: []};
								$scope.addons.rebates.selected.forEach(function(value, key, map){
									if($scope.fields[ind]['MONTHLY_TERM'].value >= value.month_from && $scope.fields[ind]['MONTHLY_TERM'].value <= value.month_to){
										if(value.type === 'auto'){
											if(value.cat_desc === 'rebate'){
												$scope.fields[ind]['AUTO_REBATES']['value'] += parseFloat(value.cash);
											} else if(value.cat_desc === 'dealercash'){
												$scope.fields[ind]['AUTO_DEALERCASH']['value'] += parseFloat(value.cash);
											}
										} else if(value.type === 'custom'){
											if(value.cat_desc === 'rebate'){
												$scope.fields[ind]['CUSTOM_REBATES']['value'] += parseFloat(value.cash);
											} else if(value.cat_desc === 'dealercash'){
												$scope.fields[ind]['CUSTOM_DEALERCASH']['value'] += parseFloat(value.cash);
											}
											$scope.fields[ind]['custom_rebate_signature_ids'].value.push(value.signatureID);
										}
									}
								});
							});
						}
					},
					/**
					 * @member {Object} for provide multiselect
					 */
					warranties: {
						warrantyList: [],
						selected: [],
						warrantyTitles: [],
						events: {
							onItemSelect: function(item){
								if(angular.isDefined($scope.addons.warranties.selected)){
									$scope.addons.warranties.selected = $scope.addons.warranties.selected.filter(function(selectedItem, key, map){
										return selectedItem.liveRequestWarrantyId !== item.liveRequestWarrantyId || selectedItem.id === item.id;});
								}
							},
						},
						extraSettings: {
							styleActive: true,
							showCheckAll: false,
							groupByTextProvider: function(groupValue) {  return $scope.addons.warranties.warrantyTitles[groupValue]; }, groupBy: 'liveRequestWarrantyId',
						},
						/**
						 * init warranties Object
						 *
						 * @function initWarranties
						 * @param term
						 */
						initWarranties: function (term) {
							PriceCalculator.getLiverequestWarranties($scope.additionalConfig.liverequest_id, term).then(function (data) {
								var obj = JSON.parse(data.data);
								var warrantyList = obj.available;
								$scope.addons.warranties.warrantyList = warrantyList;
								$scope.addons.warranties.selected = warrantyList.filter(function(selectedItem, key, map){return obj.selected.includes(selectedItem.id)});
								$scope.addons.warranties.warrantyTitles = obj.warrantyTitles;
							}).catch(function (response) {
								console.log(response);
							});
						},
						buildProviderKeys: function () {
							Object.keys($scope.fields).forEach(function (ind){
								$scope.fields[ind]['WARRANTY'] = {disabled: false, value: 0};
								if(angular.isDefined($scope.addons.warranties.selected)){
									$scope.addons.warranties.selected.forEach(function(value, key, map){
										$scope.fields[ind]['WARRANTY']['value'] += parseFloat(value.price);
									});
								}
							});
						}
					},
					dosTypes: ['Taxes & Fees', 'Customize your own'],
					monthlyTerms: [24, 27, 30, 36, 39, 42],
					milesPerYear: [10000, 12000, 15000],
					getAvailableMonthlyTerms:  function (liverequest_id) {
						switch ($scope.additionalConfig.liverequest_type) {
							case 1:
								PriceCalculator.getAvailableResidualTerms(liverequest_id).then(function (data) {
									if(data.data.length > 0){
										$scope.addons.monthlyTerms = data.data;
									}
								}).catch(function (response) {
									console.log(response);
								});
								break;
							case 2:
								PriceCalculator.getAvailableFinanceTerms(liverequest_id).then(function (data) {
									if(data.data.length > 0){
										$scope.addons.monthlyTerms = data.data;
									}
								}).catch(function (response) {
									console.log(response);
								});
								break;
						}
					},
					getAvailableMilesPerYear:  function (make) {
						switch ($scope.additionalConfig.liverequest_type) {
							case 1:
								DealerinventoryCar.getMilesByMake(make).then(function(data){
									$scope.addons.milesPerYear = [];
									data.data.forEach(function(item, i, arr) {
										$scope.addons.milesPerYear.push(item.value);
									});
								}).catch(function(response){
									console.log(response);
								});
								break;
							case 2:
								DealerinventoryCar.getMilesByMake(make).then(function(data){
									$scope.addons.milesPerYear = [];
									data.data.forEach(function(item, i, arr) {
										$scope.addons.milesPerYear.push(item.value);
									});
								}).catch(function(response){
									console.log(response);
								});
								break;
						}
					}
				};


				$scope.initCalculatorMode = function(){
					switch($scope.additionalConfig.mode) {
						case 1:
							$scope.isManualMode = true;
							break;
						case 2:
							$scope.isIgniteMode = true;
							break;
						case 3:
							$scope.isLiverequestMode = true;
							break;
					}
				};

				$scope.initLiverequestOverrideScheme = function(){
					//If we have overrides and override scheme is not empty, build model and
					//enter edit mode after it
					PriceCalculator.getLiverequestServiceDataForProvider($scope.additionalConfig.liverequest_id).then(function (data) {
						var go_to_edit_mode = false;
						if(data.data){
							$scope.overrideInput = {};
							Object.keys(data.data).forEach(function (ind){
								$scope.overrideInput[ind] = {};
								if(data.data[ind]['override_active']){
									data.data[ind]['override_active'].forEach(function(item){
										go_to_edit_mode = true;
										$scope.overrideInput[ind][item] = {};
										$scope.overrideInput[ind][item]['checked'] = true;
									});
								}
							});
						}

						if(go_to_edit_mode) $scope.edit_mode();

					}).catch(function (response) {
						console.log(response);
					})

				};

				$scope.initInputModels = function(){
					Object.keys($scope.config).forEach(function (ind){
						var type = $scope.fields[ind] = {};
						$scope.config[ind].fields.forEach(function(value, key, map){
							type[value.name] = {};
							if($scope.isManualMode){
								//In manual mode all fields without override are not disabled
								type[value.name].disabled = false;
							} else if($scope.isIgniteMode || $scope.isLiverequestMode){
								//In liverequest or ignite mode all fields are disabled by default
								type[value.name].disabled = true;
								//Fields with overrides property always starts as disabled
								if(value.hasOwnProperty('overrides')){
									type[value.name].disabled = true;
								}
							}



							//No matter on mode this fields will load disabled or not based on value
							if(value.hasOwnProperty('active_on_page_load')){
								type[value.name].disabled = !value.active_on_page_load;
							}

						});
					});
				};

				$scope.loadModeldsWithDataFromLiverequest = function(){
					if($scope.additionalConfig.hasOwnProperty('liverequest_id')){
						PriceCalculator.getLiverequestDataForProvider($scope.additionalConfig.liverequest_id).then(function (data) {
							$scope.addons.getAvailableMonthlyTerms($scope.additionalConfig.liverequest_id);
							$scope.addons.getAvailableMilesPerYear($scope.additionalConfig.liverequest_make);
							//According to mapping lets fill the init values from Liverequest
							var result = data.data;
							Object.keys(result).forEach(function (ind){
								Object.keys(result[ind]).forEach(function (property){
									$scope.fields[ind][property].value = $scope.decorators.init(property, 'in', {value: result[ind][property]});
								});
							});
							$scope.widgetPreloader.widgetSuccessfullyLoaded = true;
						})
							.catch(function (response) {
								console.log(response);
							});
					} else {
						console.error('No liverequest_id given in config');
					}
				};

				$scope.initialization = function(config){
					config = JSON.parse(config);
					$scope.additionalConfig = config;

					PriceCalculator.init($scope.additionalConfig).then(function (data) {
						$scope.config = data.data;
						$scope.initCalculatorMode();
						$scope.initInputModels();

						//In liverequest mode on page load we should load inputs with data from liverequest
						if($scope.isLiverequestMode){
							$scope.liverequestFees.load();
							$scope.liverequestUsedRates.load();

							$scope.addons.rebates.initCustomRebates($scope.additionalConfig.liverequest_monthly_term);
							$scope.addons.warranties.initWarranties($scope.additionalConfig.liverequest_monthly_term);
							$scope.loadModeldsWithDataFromLiverequest();
							$scope.initLiverequestOverrideScheme();


						} else {
							$scope.widgetPreloader.widgetSuccessfullyLoaded = true;
						}

					})
						.catch(function (response) {
							console.log(response);
						});

				};

				$scope.override = function(group, overrideInputName){
					//Change disable fields map based on dependency config
					var type = $scope.fields[group];
					type[overrideInputName].disabled = !type[overrideInputName].disabled;
				};

				$scope.save_calculation = function () {

					$scope.widgetPreloader.widgetSuccessfullyLoaded = false;
					$scope.decorators.processOut();

					//update rebates
					switch ($scope.additionalConfig.liverequest_type) {
						case 1:
							PriceCalculator.updateLiverequestRebates($scope.additionalConfig.liverequest_id, $scope.addons.rebates.selected).then(function (data) {
									//update warranties
									PriceCalculator.updateLiverequestAdditional($scope.additionalConfig.liverequest_id, $scope.addons.warranties.selected, $scope.additionalConfig.liverequest_dos_type).then(function (data) {
											//update liverequest fees
											PriceCalculator.updateLiverequestFees($scope.additionalConfig.liverequest_id, $scope.liverequestFees.collection).then(function (data) {

												}
											).catch(function (response) {
												console.log(response);
											});
										}
									).catch(function (response) {
										console.log(response);
									});
								}
							).catch(function (response) {
								console.log(response);
							});
							break;
						case 2:
							PriceCalculator.updateLiverequestRebates($scope.additionalConfig.liverequest_id, $scope.addons.rebates.selected).then(function (data) {
									//update liverequest fees
									PriceCalculator.updateLiverequestFees($scope.additionalConfig.liverequest_id, $scope.liverequestFees.collection).then(function (data) {
											//update liverequest used rates
											PriceCalculator.updateLiverequestUsedRates($scope.additionalConfig.liverequest_id, $scope.liverequestUsedRates.collection).then(function (data) {
												//update warranties
												PriceCalculator.updateLiverequestAdditional($scope.additionalConfig.liverequest_id, $scope.addons.warranties.selected, $scope.additionalConfig.liverequest_dos_type).then(function (data) {})
													.catch(function (response) {
													console.log(response);
												});
												}
											).catch(function (response) {
												console.log(response);
											});
										}
									).catch(function (response) {
										console.log(response);
									});
								}
							).catch(function (response) {
								console.log(response);
							});
							break;
					}


					//update calculation based on liverequest type
					PriceCalculator.updateCalculation({
						fields: $scope.fields,
						config: $scope.additionalConfig
					}).then(function (data) {
							if(data.data === 1){
								//alert('Successfull update');
								//Update liverequest service data also
								PriceCalculator.updateOverrideSchemeOfLiverequest($scope.additionalConfig.liverequest_id, $scope.overrideInput).then(function (data) {

								});
							} else {
								//alert('Some error occured');
							}

							$scope.decorators.processIn();
							$scope.widgetPreloader.widgetSuccessfullyLoaded = true;

						}
					).catch(function (response) {
						console.log(response);
					});
				};

				$scope.auto_calculate = function(){
					if($scope.editMode === 1){
						$scope.calculate();
					}
				};

				$scope.setWarrantiesByTerms = function(term)
				{
					$scope.addons.warranties.initWarranties(term);

				};
				$scope.setRebatesByTerms = function(term)
				{
					$scope.addons.rebates.initCustomRebates(term);

				};
				$scope.calculate = function () {
					$scope.liverequestFees.buildProviderKeys();
					$scope.addons.rebates.buildProviderKeys();
					switch ($scope.additionalConfig.liverequest_type) {
						case 1:

							break;
						case 2:
							$scope.liverequestUsedRates.buildProviderKeys();
							break;
					}
					$scope.addons.warranties.buildProviderKeys();

					$scope.widgetPreloader.widgetSuccessfullyLoaded = false;
					$scope.decorators.processOut();

					//Form data for input and calculate with ajax call
					PriceCalculator.calculate($scope.fields, $scope.additionalConfig.liverequest_id, $scope.editMode, $scope.additionalConfig.liverequest_dos_type).then(function (data) {
						Object.keys(data.data).forEach(function (ind){
							Object.keys(data.data[ind]).forEach(function (key){
								var output = data.data[ind];
								var type = $scope.fields[ind];
								if(type.hasOwnProperty(key)){
									type[key].value = $scope.decorators.init(key, 'in', {value: output[key]});
								}
							});
						});
						$scope.widgetPreloader.widgetSuccessfullyLoaded = true;
					})
						.catch(function (response) {
							console.log(response);
						});

				};

				$scope.update_incentives = function(){
					if (confirm("Saved incentives will be updated with actual incentives for this car and cant be restored. Proceed?")) {
						PriceCalculator.updateLiverequestStack($scope.additionalConfig.liverequest_id).then(function (data) {
							location.reload();
						}).catch(function (response) {
							console.log(response);
						});
					}
				};

				$scope.edit_mode = function () {
					if($scope.editMode === 1){
						$scope.editMode = 2;
					} else if($scope.editMode === 2){
						$scope.editMode = 1;
					}

					Object.keys($scope.config).forEach(function (ind){
						$scope.config[ind].fields.forEach(function(value, key, map){

							if(!value.hasOwnProperty('overrides') && !value.hasOwnProperty('active_on_page_load')){
								$scope.fields[ind][value.name].disabled = !$scope.fields[ind][value.name].disabled;

							}

							if(angular.isDefined($scope.overrideInput)){
								if($scope.overrideInput.hasOwnProperty(ind)){
									if($scope.overrideInput[ind].hasOwnProperty(value.name)){
										if($scope.editMode === 1){
											$scope.fields[ind][value.name].disabled = true;
										} else if($scope.editMode === 2){
											$scope.fields[ind][value.name].disabled = !$scope.overrideInput[ind][value.name].checked;
										}
									}
								}
							}

						});
					});

				};

			}])

		.factory('PriceCalculator', ['$http', function PriceCalculator ($http){
			return {
				init: function(additional_config){
					return $http({method: 'POST', url: '/api/calculator/init-calculator', data: {
							'additional_config': additional_config
						}});
				},
				calculate: function (calculatorData, liverequest_id, edit_mode, dos_type){
					return $http({method: 'POST', url: '/api/calculator/get-calculations', data: {
							'calculator_data':calculatorData,
							'edit_mode': edit_mode,
							'liverequest_id': liverequest_id,
							'dostype': dos_type
						}});
				},
				updateOverrideSchemeOfLiverequest: function(id, override_scheme){
					return $http({method: 'POST', url: '/api/calculator/update-liverequest-override-scheme', data: {
							'liverequest_id':id,
							'override_scheme':override_scheme
						}});
				},
				getLiverequestDataForProvider: function(id){
					return $http({method: 'POST', url: '/api/calculator/get-liverequest-fields', data: {
							'liverequest_id':id,
						}});
				},
				getLiverequestServiceDataForProvider: function(id){
					return $http({method: 'POST', url: '/api/calculator/get-liverequest-service-field', data: {
							'liverequest_id':id,
						}});
				},
				updateCalculation: function (input_data) {
					return $http({method: 'POST', url: '/api/calculator/assign-calculation', data: {
							'input_data': input_data
						}});
				},
				updateLiverequestStack: function (id) {
					return $http({method: 'POST', url: '/api/calculator/update-liverequest-stack', data: {
							'liverequest_id':id,
						}});
				},
				getAvailableResidualTerms: function (id) {
					return $http({method: 'POST', url: '/api/calculator/get-available-residual-terms', data: {
							'liverequest_id':id,
						}});
				},
				getAvailableFinanceTerms: function (id) {
					return $http({method: 'POST', url: '/api/calculator/get-available-finance-terms', data: {
							'liverequest_id':id,
						}});
				},
				loadLiverequestFees: function (id) {
					return $http({method: 'POST', url: '/api/calculator/get-liverequest-fees', data: {
							'liverequest_id':id,
						}});
				},
				loadLiverequestUsedRates: function (id) {
					return $http({method: 'POST', url: '/api/calculator/get-liverequest-used-rates', data: {
							'liverequest_id':id,
						}});
				},
				updateLiverequestFees: function (id, fees_collection) {
					return $http({method: 'POST', url: '/api/calculator/update-liverequest-fees', data: {
							'liverequest_id':id,
							'fees_collection':fees_collection
						}});
				},
				updateLiverequestUsedRates: function (id, rates_collection) {
					return $http({method: 'POST', url: '/api/calculator/update-liverequest-used-rates', data: {
							'liverequest_id':id,
							'rates_collection':rates_collection
						}});
				},
				loadCustomRebatesForLiverequest: function (id,term) {
					return $http({method: 'POST', url: '/api/calculator/load-custom-rebates-for-liverequest', data: {
							'liverequest_id':id,
							'term':term
						}});
				},
				updateLiverequestRebates: function (id, rebates_collection) {
					return $http({method: 'POST', url: '/api/calculator/update-liverequest-rebates', data: {
							'liverequest_id':id,
							'rebates_collection':rebates_collection
						}});
				},
				/**
				 * get liverequest warranties
				 *
				 * @function getLiverequestWarranties
				 * @param id
				 * @param terms
				 * @returns {*}
				 */
				getLiverequestWarranties: function(id, terms)
				{
					return $http({method: "POST", url: "/api/calculator/get-liverequest-warranties", data: {
							'liverequest_id': id,
							'terms': terms,
						}})
				},
				updateLiverequestAdditional: function (id, warranties_collection, dos_type) {
					return $http({method: 'POST', url: '/api/calculator/update-liverequest-additional', data: {
							'liverequest_id':id,
							'warranties_collection':warranties_collection,
							'dostype': dos_type
						}});
				},
			};
		}]);




})(angular, jQuery)
