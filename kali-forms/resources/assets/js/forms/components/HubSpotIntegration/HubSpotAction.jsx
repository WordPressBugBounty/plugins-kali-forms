import Grid from '@material-ui/core/Grid'
import TextField from '@material-ui/core/TextField'
import MenuItem from '@material-ui/core/MenuItem'
import ContactFormFieldsMap from './ContactFormFieldsMap'
import OwnerSelection from './OwnerSelection'
import React, { useEffect, useState } from 'react';
import AdditionalFormFields from './AdditionalFormFields'
import ActionConditionalLogic from './ActionConditionalLogic'
import HubSpotSectionHeader from './HubSpotSectionHeader'
import BootstrapInput from './../BootstrapInput';
import FormControl from '@material-ui/core/FormControl';
import InputLabel from '@material-ui/core/InputLabel';
import FormHelperText from '@material-ui/core/FormHelperText';
import Select from '@material-ui/core/Select';
const { __ } = wp.i18n;

const HubSpotAction = (props) => {
	// Current action state
	let currentData = props.hubspotData
	const [hubSpotAction, setHubSpotAction] = useState(currentData.hubSpotAction);
	const [hubSpotFormName, setHubSpotFormName] = useState(currentData.hubSpotFormName);
	const [leadStatus, setLeadStatus] = useState(currentData.leadStatus);
	const [lifecycleStage, setLifecycleStage] = useState(currentData.lifecycleStage);
	const [contactOwnerOption, setContactOwnerOption] = useState(currentData.contactOwnerOption);
	const [contactOwner, setContactOwner] = useState(currentData.contactOwner);
	const [conditionalOwner, setConditionalOwner] = useState(currentData.conditionalOwner);
	const [formFieldsMap, setFormFieldsMap] = useState(currentData.formFieldsMap);
	const [additionalFormFields, setAdditionalFormFields] = useState(currentData.additionalFormFields);
	const [conditionalLogic, setConditionalLogic] = useState(currentData.conditionalLogic)
	const [conditionalLogicConditions, setConditionalLogicConditions] = useState(currentData.conditionalLogicConditions);
	const [guid, setGuid] = useState(currentData.guid);
	const [portalId, setPortalId] = useState(currentData.portalId);

	// Initial Load
	useEffect(() => {
		let currentData = props.hubspotData;
		setHubSpotAction(currentData.hubSpotAction);
		setHubSpotFormName(currentData.hubSpotFormName);
		setLeadStatus(currentData.leadStatus);
		setLifecycleStage(currentData.lifecycleStage);
		setContactOwnerOption(currentData.contactOwnerOption);
		setContactOwner(currentData.contactOwner);
		setConditionalOwner(currentData.conditionalOwner);
		setFormFieldsMap(currentData.formFieldsMap);
		setAdditionalFormFields(currentData.additionalFormFields);
		setConditionalLogic(currentData.conditionalLogic);
		setConditionalLogicConditions(currentData.conditionalLogicConditions);
		setGuid(currentData.guid);
		setPortalId(currentData.portalId);
	}, [props.idx])

	// When something changes, we need to update the values that are saved in database
	useEffect(() => {
		props.setHubspotDataByIndex(props.idx, { hubSpotAction, hubSpotFormName, leadStatus, lifecycleStage, contactOwner, contactOwnerOption, conditionalOwner, formFieldsMap, additionalFormFields, conditionalLogic, conditionalLogicConditions, guid, portalId, idx: props.idx })
	}, [hubSpotAction, hubSpotFormName, leadStatus, lifecycleStage, contactOwner, contactOwnerOption, conditionalOwner, formFieldsMap, additionalFormFields, conditionalLogic, conditionalLogicConditions, guid, portalId])

	return (
		<React.Fragment>
			<HubSpotSectionHeader
				header={__('HubSpot Action', 'kali-forms')}
				backButton={true}
				backButtonAction={props.goBack} />
			<Grid container direction="row" spacing={4}>
				<Grid item xs={12}>
					<FormControl>
						<InputLabel shrink>
							{__('HubSpot action name', 'kali-forms')}
						</InputLabel>
						<BootstrapInput
							required
							value={hubSpotAction}
							onChange={e => setHubSpotAction(e.target.value)}
							fullWidth={true}
							variant="filled"
							placeholder={__('HubSpot Action', 'kali-forms')}
						/>
						<FormHelperText>{__('Name of the HubSpot action', 'kali-forms')}</FormHelperText>
					</FormControl>
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={4}>
				<Grid item xs={12}>
					<FormControl>
						<InputLabel shrink>
							{__('HubSpot form name', 'kali-forms')}
						</InputLabel>
						<BootstrapInput
							required
							value={hubSpotFormName}
							onChange={e => setHubSpotFormName(e.target.value)}
							fullWidth={true}
							variant="filled"
							placeholder={__('Contact form', 'kali-forms')}
						/>
						<FormHelperText>{__('Name of the contact form that will be created in your HubSpot account')}</FormHelperText>
					</FormControl>
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={4}>
				<Grid item xs={12}>
					<FormControl>
						<InputLabel shrink>
							{__('Lead status', 'kali-forms')}
						</InputLabel>
						<Select
							multiple={false}
							input={<BootstrapInput />}
							value={leadStatus}
							onChange={e => setLeadStatus(e.target.value)}
						>
							<MenuItem key="select-option" value="">
								{__('Select an option', 'kali-forms')}
							</MenuItem>
							<MenuItem key="NEW" value="NEW">
								{__('New', 'kali-forms')}
							</MenuItem>
							<MenuItem key="OPEN" value="OPEN">
								{__('Open', 'kali-forms')}
							</MenuItem>
							<MenuItem key="IN_PROGRESS" value="IN_PROGRESS">
								{__('In Progress', 'kali-forms')}
							</MenuItem>
							<MenuItem key="OPEN_DEAL" value="OPEN_DEAL">
								{__('Open Deal', 'kali-forms')}
							</MenuItem>
							<MenuItem key="UNQUALIFIED" value="UNQUALIFIED">
								{__('Unqualified', 'kali-forms')}
							</MenuItem>
							<MenuItem key="ATTEMPTED_TO_CONTACT" value="ATTEMPTED_TO_CONTACT">
								{__('Attempted to Contact', 'kali-forms')}
							</MenuItem>
							<MenuItem key="CONNECTED" value="CONNECTED">
								{__('Connected', 'kali-forms')}
							</MenuItem>
							<MenuItem key="BAD_TIMING" value="BAD_TIMING">
								{__('Bad Timing', 'kali-forms')}
							</MenuItem>
						</Select>
						<FormHelperText>{__('Lead status of the newly created contact', 'kali-forms')}</FormHelperText>
					</FormControl>
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={4}>
				<Grid item xs={12}>
					<FormControl>
						<InputLabel shrink>
							{__('Life cycle stage', 'kali-forms')}
						</InputLabel>
						<Select
							multiple={false}
							input={<BootstrapInput />}
							value={lifecycleStage}
							onChange={e => setLifecycleStage(e.target.value)}
						>
							<MenuItem key="select-option" value="">
								{__('Select an option', 'kali-forms')}
							</MenuItem>
							<MenuItem key="subscriber" value="subscriber">
								{__('Subscriber', 'kali-forms')}
							</MenuItem>
							<MenuItem key="lead" value="lead">
								{__('Lead', 'kali-forms')}
							</MenuItem>
							<MenuItem key="marketingqualifiedlead" value="marketingqualifiedlead">
								{__('Marketing Qualified Lead', 'kali-forms')}
							</MenuItem>
							<MenuItem key="salesqualifiedlead" value="salesqualifiedlead">
								{__('Sales Qualified Lead', 'kali-forms')}
							</MenuItem>
							<MenuItem key="opportunity" value="opportunity">
								{__('Opportunity', 'kali-forms')}
							</MenuItem>
							<MenuItem key="customer" value="customer">
								{__('Customer', 'kali-forms')}
							</MenuItem>
							<MenuItem key="evangelist" value="evangelist">
								{__('Evangelist', 'kali-forms')}
							</MenuItem>
							<MenuItem key="other" value="other">
								{__('Other', 'kali-forms')}
							</MenuItem>
						</Select>
						<FormHelperText>{__('Life cycle stage value of the contact', 'kali-forms')}</FormHelperText>
					</FormControl>
				</Grid>
			</Grid>

			<HubSpotSectionHeader header={__('Owner', 'kali-forms')} />
			<OwnerSelection
				setContactOwnerOption={setContactOwnerOption}
				contactOwnerOption={contactOwnerOption}
				setContactOwner={setContactOwner}
				contactOwner={contactOwner}
				conditionalOwner={conditionalOwner}
				setConditionalOwner={setConditionalOwner}
			/>

			<HubSpotSectionHeader header={__('Map Form Fields', 'kali-forms')} />
			<ContactFormFieldsMap setFormFieldsMap={setFormFieldsMap} formFieldsMap={formFieldsMap} />

			<HubSpotSectionHeader header={__('Additional Form Fields', 'kali-forms')} />
			{
				additionalFormFields.map((formField, idx) => (
					<AdditionalFormFields
						key={idx}
						additionalFieldIndex={idx}
						additionalFormFields={additionalFormFields}
						setAdditionalFormFields={setAdditionalFormFields}
						hubSpotProperty={formField.hubspotProperty}
						assignedFormField={formField.assignedFormField}
					/>
				))
			}

			<HubSpotSectionHeader header={__('Conditional Logic', 'kali-forms')} />
			<ActionConditionalLogic
				conditionalLogic={conditionalLogic}
				conditionalLogicConditions={conditionalLogicConditions}
				setConditionalLogic={setConditionalLogic}
				setConditionalLogicConditions={setConditionalLogicConditions}
			/>

			<input
				type="hidden"
				value={guid}
				readOnly={true}
			/>
			<input
				type="hidden"
				value={portalId}
				readOnly={true}
			/>

		</React.Fragment>
	);
}
export default HubSpotAction;
