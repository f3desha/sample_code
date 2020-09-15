<?php
/**
 * @var $image_url string
 */
?>
<div class="overlay_seo_catalog">
	<div class="wrapper_seo_catalog_popup">
		<div class="wrapper_seo_catalog_popup_container">
			<div class="seo_catalog_popup">
				<div class="close_popup">
					<img src="/statics/images/img/close.png" alt="close" />
				</div>
				<div class="top_popup">
					<div class="wrap_text">
						<h2>
							{{ seocatalogObject.zipLocatorForm.objectResponse.content.title }}
						</h2>
						<p ng-bind-html="seocatalogObject.zipLocatorForm.objectResponse.content.description">
						</p>
					</div>
					<div class="wrap_img">
						<img src="<?=$image_url?>" alt="car" />
					</div>
				</div>
				<div class="content_popup">
					<form ng-submit="seocatalogObject.zipLocatorForm.sellLead($event); seocatalogObject.zipLocatorForm.clickSubmit=true;" ng-init="seocatalogObject.zipLocatorForm.init()">
						<div class="form_content_seo">
							<div class="form_group_seo">
								<label for="">
									<span class="req">First Name</span>
									<span class="with_icon">
										<img src="/statics/images/img/icon4.png" alt="car" />
										<input type="text" ng-model="seocatalogObject.zipLocatorForm.fields.personal.name" name="name" placeholder="First Name" required />
									</span>
								</label>
								<label for="">
									<span class="req">Last Name</span>
									<input type="text" ng-model="seocatalogObject.zipLocatorForm.fields.personal.last_name" name="last_name" placeholder="Last Name" required />
								</label>
								<label for="">
									<span class="req">Phone</span>
									<input type="tel" ng-model="seocatalogObject.zipLocatorForm.fields.contact.phone" name="phone" placeholder="Phone" required />
								</label>
								<label for="">
									<span class="req">Email</span>
									<input type="email" ng-model="seocatalogObject.zipLocatorForm.fields.contact.email" name="email" placeholder="Email" required />
								</label>
							</div>
							<label for="">
								<span class="req">Address</span>
								<span class="with_icon">
									<img src="/statics/images/img/icon3.png" alt="car" />
									<input type="text" ng-model="seocatalogObject.zipLocatorForm.fields.residence.address" name="city" placeholder="Address" required>
								</span>
							</label>
							<div class="form_group_seo_second">
								<label for="">
									<span class="req">City</span>
									<input type="text" ng-model="seocatalogObject.zipLocatorForm.fields.residence.city" name="city" placeholder="City" required disabled/>
								</label>
								<label for="">
									<span class="req">State</span>
									<input type="text" ng-model="seocatalogObject.zipLocatorForm.fields.residence.state" name="state" placeholder="State" required disabled/>
								</label>
								<label for="">
									<span class="req">ZIP</span>
                                    <input
                                            type="number"
                                            ng-model="seocatalogObject.zipLocatorForm.fields.residence.zip"
                                            ng-change="seocatalogObject.zipLocatorForm.changeZip()"
                                            name="zip"
                                            placeholder="ZIP"
                                            required
                                            max="99999"
                                            min="111"
                                    />
                                </label>
							</div>
							<div class="form_group_seo">
								<label for="">
									<span class="req">Best Time to Contact</span>
									<span class="with_icon">
										<img src="/statics/images/img/icon2.png" alt="car" />
										<span class="dropdown drop-block">
											<button ng-cloak class="btn_select dropdown-toggle" type="button" id="dropdownMenu3" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
												<span class="caret"></span> {{seocatalogObject.zipLocatorForm.selectModels.timeContact.getById(seocatalogObject.zipLocatorForm.fields.contact.timeContact).label}}
											</button>
											<ul class="dropdown-menu" aria-labelledby="dropdownMenu3">
												<li ng-cloak ng-repeat="timeContact in seocatalogObject.zipLocatorForm.selectModels.timeContact.list">
                                                <a ng-click="seocatalogObject.zipLocatorForm.fields.contact.timeContact = timeContact.id;"
                                                   href=""
                                                >{{timeContact.label}}</a>
                                                </li>
											</ul>
										</span>
									</span>
								</label>
								<label for="">
									<span class="req">Desired Timeframe</span>
									<span class="with_icon">
<!--										<img src="/statics/images/offered.png" alt="car" />-->
										<span class="dropdown drop-block">
											<button ng-cloak class="btn_select dropdown-toggle"
                                                    type="button" id="dropdownMenu3" data-toggle="dropdown" aria-haspopup="true"
                                                    aria-expanded="true"
                                                    title="Time frame represented in days in which the consumer intends to purchase the vehicle."
                                            >
												<span class="caret"></span> {{seocatalogObject.zipLocatorForm.selectModels.timeFrame.getById(seocatalogObject.zipLocatorForm.fields.timeFrame).label}}
											</button>
											<ul class="dropdown-menu" aria-labelledby="dropdownMenu3">
												<li ng-cloak ng-repeat="timeFrame in seocatalogObject.zipLocatorForm.selectModels.timeFrame.list">
                                                <a ng-click="seocatalogObject.zipLocatorForm.fields.timeFrame = timeFrame.id;"
                                                   href=""
                                                >{{timeFrame.label}}</a>
                                                </li>
											</ul>
										</span>
									</span>
								</label>
							</div>
							<label for="">
								<span>Special Requests</span>
								<textarea ng-model="seocatalogObject.zipLocatorForm.fields.comments" name="comments" placeholder="Type here ..."></textarea>
							</label>
                            <div class="form_group_seo">
                                <div class="dealers"  ng-show="seocatalogObject.zipLocatorForm.selectModels.dealers.list.length > 0">
                                    <strong>
                                        Select Dealers to Receive Competitive Offers
                                    </strong>
                                    <p>
                                        <img src="/statics/images/img/icon.png" alt="car" />
                                        <span ng-class="(seocatalogObject.zipLocatorForm.checkDealers())? 'text-danger':''">You must select at least one dealer(up to 5) to continue.</span>
                                    </p>
                                    <div class="text_seo_form"
                                         ng-repeat="dealer in seocatalogObject.zipLocatorForm.selectModels.dealers.list"
                                         ng-click="seocatalogObject.zipLocatorForm.checkDealer(dealer); "
                                         ng-class="(dealer.checked)? 'checked' : ''"
                                    >
                                        <strong>
                                            {{dealer.Name}}
                                        </strong>
                                        <p>
                                            {{dealer.Street}}, {{dealer.City}}, {{dealer.State}},
                                        </p>
                                    </div>
                                </div>
                            </div>
						</div>
						<div class="bottom_content_form">
							<div class="wrap_btn">
								<input type="submit" value="Submit">
							</div>
							<p>
								<b>We take your privacy seriously.</b> By submitting your request, you agree that you are expressly providing consent to have Carvoy partners contact your directly. You also agree that we and our parnters may use an autodialer to text or call, and that portion of any call may be pre-recorded.
							</p>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>