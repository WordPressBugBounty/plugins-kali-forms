import FormGroup from '@material-ui/core/FormGroup';
import Grid from '@material-ui/core/Grid';
import React from 'react';
import { observer } from "mobx-react";
import { store } from "./../../store/store";
import BootstrapInput from './../BootstrapInput';
import InputLabel from '@material-ui/core/InputLabel';
import FormControl from '@material-ui/core/FormControl';
import Container from './../LayoutComponents/Container';
import SectionTitle from './../Misc/SectionTitle'
import Checkbox from './../Misc/Checkbox';
import FormControlLabel from './../Misc/FormControlLabel';
import FormFieldMapper from './../Misc/FormFieldMapper';
import FormHelperText from '@material-ui/core/FormHelperText';
const { __ } = wp.i18n;
const FormSpam = observer((props) => {

	return (
		<React.Fragment>
			<Container maxWidth="md">
				<SectionTitle title="Akismet" />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={12}>
						<FormGroup row>
							<FormControlLabel
								control={
									<Checkbox
										disabled={KaliFormsObject.akismetKey === '0'}
										checked={KaliFormsObject.akismetKey !== '0' && store._FORM_INFO_.akismet === '1'}
										onChange={e => store._FORM_INFO_.akismet = e.target.checked ? '1' : '0'}
									/>
								}
								label={__('Enable Akismet anti spam', 'kali-forms')}
							/>
						</FormGroup>
						<If condition={KaliFormsObject.akismetKey === '0'}>
							<FormHelperText>
								{__('You can enable Akismet spam protection on your form by installing and activating the Akismet plugin and adding your API key in the settings.', 'kali-forms')}
							</FormHelperText>
						</If>
					</Grid>
					<If condition={KaliFormsObject.akismetKey !== '0' && store._FORM_INFO_.akismet === '1'}>
						<Grid item xs={12}>
							<FormFieldMapper
								fieldsToMap={[
									{ id: 'firstName', label: __('First name', 'kali-forms') },
									{ id: 'lastName', label: __('Last name', 'kali-forms') },
									{ id: 'email', label: __('Email', 'kali-forms') },
									{ id: 'message', label: __('Message', 'kali-forms') },
								]}
								values={store._FORM_INFO_.akismetFields}
								onChange={val => store._FORM_INFO_.akismetFields = val}
							/>
						</Grid>
					</If>
				</Grid>
				<SectionTitle title="Honeypot" />
				<Grid container direection="row" spacing={3}>
					<Grid item xs={12}>
						<FormGroup>
							<FormControlLabel
								control={
									<Checkbox
										checked={store._FORM_INFO_.honeypot === '1'}
										onChange={e => store._FORM_INFO_.honeypot = e.target.checked ? '1' : '0'}
									/>
								}
								label={__('Honeypot anti-spam', 'kali-forms')}
							/>
							<If condition={store._FORM_INFO_.googleSiteKey !== '' && store._FORM_INFO_.googleSecretKey !== ''}>
								<FormControlLabel
									control={
										<Checkbox
											checked={store._FORM_INFO_.removeCaptchaForLoggedUsers === '1'}
											onChange={e => store._FORM_INFO_.removeCaptchaForLoggedUsers = e.target.checked ? '1' : '0'}
										/>
									}
									label={__('Remove captcha for logged user', 'kali-forms')}
								/>
							</If>
						</FormGroup>
					</Grid>
				</Grid>
				<SectionTitle title="Turnstile" />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={12}>
						<FormControlLabel
							control={
								<Checkbox
									checked={store._FORM_INFO_.turnstileEnabled === '1'}
									onChange={e => store._FORM_INFO_.turnstileEnabled = e.target.checked ? '1' : '0'}
									/>
								}
								label={__('Enable Turnstile anti spam', 'kali-forms')}
						/>
						<FormHelperText>
							{__('Turnstile is a free anti-spam service that protects your form from spam. It is a simple and effective way to prevent spam. It is a free service that is easy to use and setup.', 'kali-forms')}
						</FormHelperText>
					</Grid>
					<If condition={store._FORM_INFO_.turnstileEnabled === '1'}>
						<Grid item xs={6}>
							<FormControl>
								<InputLabel shrink>
									{__('Turnstile site key', 'kali-forms')}
								</InputLabel>
								<BootstrapInput
									value={store._FORM_INFO_.turnstileSiteKey}
									onChange={e => store._FORM_INFO_.turnstileSiteKey = e.target.value}
									variant="filled"
									fullWidth={true}
								/>
							</FormControl>
						</Grid>
						<Grid item xs={6}>
							<FormControl>
								<InputLabel shrink>
									{__('Turnstile secret key', 'kali-forms')}
								</InputLabel>
								<BootstrapInput
									value={store._FORM_INFO_.turnstileSecretKey}
									onChange={e => store._FORM_INFO_.turnstileSecretKey = e.target.value}
									variant="filled"
									fullWidth={true}
									/>
								</FormControl>
							</Grid>
						</If>
				</Grid>
				<SectionTitle title="Google" />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__('reCAPTCHA site key', 'kali-forms')}
							</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.googleSiteKey}
								onChange={e => store._FORM_INFO_.googleSiteKey = e.target.value}
								variant="filled"
								fullWidth={true}
							/>
						</FormControl>
					</Grid>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__('reCAPTCHA secret key', 'kali-forms')}
							</InputLabel>
							<BootstrapInput
								value={store._FORM_INFO_.googleSecretKey}
								onChange={e => store._FORM_INFO_.googleSecretKey = e.target.value}
								variant="filled"
								fullWidth={true}
							/>
						</FormControl>
					</Grid>
				</Grid>
			</Container>
		</React.Fragment>
	);
})
export default FormSpam;
