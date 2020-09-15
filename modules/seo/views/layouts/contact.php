<?php
use yii\widgets\ActiveForm;
?>
<div class="about-new-contact clearfix">
	<div class="container">
		<div class="row">
			<div class="col-md-4 col-md-offset-4">
				<h2 class="title">Questions?</h2>
				<div class="desc">Carvoy has a team of dedicated specialists on standby to give you unbiased advice, feel free to ask us anything!</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6 col-md-offset-3 nopadding">
				<?php
					$form = ActiveForm::begin();
					?>
				<form action="#">
					<div class="form-row clearfix">
						<div class="form-group col-md-6">
							<?= $form->field($contact, 'first_name')->textInput(['placeholder' => 'Your Name'])->label('Name'); ?>
						</div>
						<div class="form-group col-md-6">
							<?= $form->field($contact, 'last_name')->textInput(['placeholder' => 'What is your Last Name'])->label('Last Name'); ?>
						</div>
					</div>
					<div class="form-row clearfix">
						<div class="form-group col-md-6">
							<?= $form->field($contact, 'phone')->textInput(['placeholder' => 'Your Phone Number'])->label('Phone'); ?>
						</div>
						<div class="form-group col-md-6">
							<?= $form->field($contact, 'email')->textInput(['placeholder' => 'Your Email Address'])->label('Email'); ?>
						</div>
					</div>
					<div class="form-row clearfix">
						<div class="form-group col-md-12">
							<?= $form->field($contact, 'body')->textarea(['placeholder' => 'How can we help you?'])->label('Message'); ?>
						</div>
					</div>
					<div class="captcha-block">
						<div style="display: inline-block;" class="g-recaptcha" data-sitekey="<?php echo Yii::$app->params['google_recaptcha']; ?>"></div>
					</div>
					<div class="form-row clearfix text-center">
						<div class="form-group col-md-12">
							<input type="submit" value="Send Message">
						</div>
						<div class="note">Note: We'll never share your contact information without your permission.</div>
					</div>
				</form>
				<?php
					ActiveForm::end();
				?>
				<div class="info">
					<a href="tel:866-615-1723" class="tel"><span class="icon"><i class="fa fa-phone" aria-hidden="true"></i></span><span class="txt">866-615-1723</span></a>
					<span class="separate"></span>
					<a href="mailto:support@carvoy.com" class="email"><span class="icon"><i class="fa fa-envelope-o" aria-hidden="true"></i></span><span class="txt">support@carvoy.com</span></a>
				</div>
			</div>
		</div>
	</div>

</div>