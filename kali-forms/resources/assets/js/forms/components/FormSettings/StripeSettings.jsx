import Grid from '@material-ui/core/Grid';
import React from 'react';
import { observer } from "mobx-react";
import { store } from "./../../store/store";
import Container from './../LayoutComponents/Container';
import SectionTitle from './../Misc/SectionTitle'
import FormFieldMapper from './../Misc/FormFieldMapper';
import BootstrapInput from './../BootstrapInput';
import InputLabel from '@material-ui/core/InputLabel';
import FormControl from '@material-ui/core/FormControl';
import Select from '@material-ui/core/Select';
import FormGroup from '@material-ui/core/FormGroup';
import Checkbox from './../Misc/Checkbox';
import MenuItem from '@material-ui/core/MenuItem';
import FormControlLabel from './../Misc/FormControlLabel';
const { __ } = wp.i18n;

const StripeSettings = observer((props) => {
	const stripeCountries = {
		AE: __('United Arab Emirates', 'kali-forms'),
		AT: __('Austria', 'kali-forms'),
		AU: __('Australia', 'kali-forms'),
		BE: __('Belgium', 'kali-forms'),
		BG: __('Bulgaria', 'kali-forms'),
		BR: __('Brasil', 'kali-forms'),
		CA: __('Canada', 'kali-forms'),
		CH: __('Switzerland', 'kali-forms'),
		CI: __('Côte d’Ivoire', 'kali-forms'),
		CR: __('Croatia', 'kali-forms'),
		CY: __('Cyprus', 'kali-forms'),
		CZ: __('Czech Republic', 'kali-forms'),
		DE: __('Germany', 'kali-forms'),
		DK: __('Denmark', 'kali-forms'),
		DO: __('Dominican Republic', 'kali-forms'),
		EE: __('Estonia', 'kali-forms'),
		ES: __('Spain', 'kali-forms'),
		FI: __('Finland', 'kali-forms'),
		FR: __('France', 'kali-forms'),
		GB: __('United Kingdom', 'kali-forms'),
		GR: __('Greece', 'kali-forms'),
		GT: __('Guatemala', 'kali-forms'),
		HK: __('Hong Kong', 'kali-forms'),
		HU: __('Hungary', 'kali-forms'),
		ID: __('Indonesia', 'kali-forms'),
		IE: __('Ireland', 'kali-forms'),
		IN: __('India', 'kali-forms'),
		IT: __('Italy', 'kali-forms'),
		JP: __('Japan', 'kali-forms'),
		LT: __('Lithuania', 'kali-forms'),
		LU: __('Luxembourg', 'kali-forms'),
		LV: __('Latvia', 'kali-forms'),
		MT: __('Malta', 'kali-forms'),
		MX: __('Mexico', 'kali-forms'),
		MY: __('Malaysia', 'kali-forms'),
		NL: __('Netherlands', 'kali-forms'),
		NO: __('Norway', 'kali-forms'),
		NZ: __('New Zealand', 'kali-forms'),
		PE: __('Peru', 'kali-forms'),
		PH: __('Philippines', 'kali-forms'),
		PL: __('Poland', 'kali-forms'),
		PT: __('Portugal', 'kali-forms'),
		RO: __('Romania', 'kali-forms'),
		SE: __('Sweden', 'kali-forms'),
		SG: __('Singapore', 'kali-forms'),
		SI: __('Slovenia', 'kali-forms'),
		SK: __('Slovakia', 'kali-forms'),
		SN: __('Senegal', 'kali-forms'),
		TH: __('Thailand', 'kali-forms'),
		TT: __('Trinidad & Tobago', 'kali-forms'),
		US: __('United States', 'kali-forms'),
		UY: __('Uruguay', 'kali-forms')
	};

	return (
		<React.Fragment>
			<Container maxWidth="md">
				<SectionTitle title="Stripe keys" />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__('Publishable key (sandbox)', 'kali-forms')}
							</InputLabel>
							<BootstrapInput
								value={store._PAYMENTS_.stripePKey}
								onChange={e => store._PAYMENTS_.stripePKey = e.target.value}
								variant="filled"
								fullWidth={true}
							/>
						</FormControl>
					</Grid>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__('Secret key (sandbox)', 'kali-forms')}
							</InputLabel>
							<BootstrapInput
								value={store._PAYMENTS_.stripeSKey}
								onChange={e => store._PAYMENTS_.stripeSKey = e.target.value}
								variant="filled"
								fullWidth={true}
							/>
						</FormControl>
					</Grid>
				</Grid>
				<Grid container direction="row" spacing={3}>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__('Publishable key (LIVE)', 'kali-forms')}
							</InputLabel>
							<BootstrapInput
								value={store._PAYMENTS_.stripePKeyLive}
								onChange={e => store._PAYMENTS_.stripePKeyLive = e.target.value}
								variant="filled"
								fullWidth={true}
							/>
						</FormControl>
					</Grid>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__('Secret key (LIVE)', 'kali-forms')}
							</InputLabel>
							<BootstrapInput
								value={store._PAYMENTS_.stripeSKeyLive}
								onChange={e => store._PAYMENTS_.stripeSKeyLive = e.target.value}
								variant="filled"
								fullWidth={true}
							/>
						</FormControl>
					</Grid>
				</Grid>
				<SectionTitle title="Stripe options" />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={6}>
						<FormGroup row>
							<FormControlLabel
								control={
									<Checkbox
										checked={store._PAYMENTS_.stripePaymentRequestButton === '1'}
										onChange={e => store._PAYMENTS_.stripePaymentRequestButton = e.target.checked ? '1' : '0'}
									/>
								}
								label={__('Add Pay Now button', 'kali-forms')}
							/>
						</FormGroup>
					</Grid>
					<Grid item xs={6}>
						<FormControl>
							<InputLabel shrink>
								{__('Account country', 'kali-forms')}
							</InputLabel>
							<Select
								value={store._PAYMENTS_.stripeCountry}
								multiple={false}
								onChange={e => store._PAYMENTS_.stripeCountry = e.target.value}
								input={<BootstrapInput />}
							>
								{Object.keys(stripeCountries).map(key => <MenuItem key={key} value={key}>{stripeCountries[key]}</MenuItem>)}
							</Select>
						</FormControl>
					</Grid>
				</Grid>
				<SectionTitle title="Stripe customer details" />
				<Grid container direction="row" spacing={3}>
					<Grid item xs={12}>
						<FormFieldMapper
							fieldsToMap={[
								{ id: 'name', label: __('Card name', 'kali-forms') },
								{ id: 'email', label: __('Email', 'kali-forms') },
								{ id: 'phone', label: __('Phone', 'kali-forms') },
								{ id: 'city', label: __('City', 'kali-forms') },
								{ id: 'country', label: __('Country (country code)', 'kali-forms') },
								{ id: 'line1', label: __('Address line 1', 'kali-forms') },
								{ id: 'line2', label: __('Address line 2', 'kali-forms') },
								{ id: 'state', label: __('State', 'kali-forms') },
								{ id: 'postal_code', label: __('Postal code', 'kali-forms') }
							]}
							values={store._PAYMENTS_.stripeFields}
							onChange={val => store._PAYMENTS_.stripeFields = val}
						/>
					</Grid>
				</Grid>

			</Container>
		</React.Fragment>
	);
})
export default StripeSettings;
