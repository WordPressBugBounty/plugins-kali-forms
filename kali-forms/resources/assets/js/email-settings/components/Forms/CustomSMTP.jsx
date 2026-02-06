import React from 'react';
import Typography from '@material-ui/core/Typography';
import Grid from '@material-ui/core/Grid';
import BootstrapInput from '../Misc/BootstrapInput'
import Checkbox from '../Misc/Checkbox';
import Select from '@material-ui/core/Select';
import MenuItem from '@material-ui/core/MenuItem';
import MostUsedOptions from '../Misc/MostUsedOptions';
const { __ } = wp.i18n;
const CustomSMTP = props => {
	let savedState = {
		host: props.settings.smtp_host,
		port: props.settings.smtp_port,
		auth: (
			props.settings.smtp_auth === ''
			|| props.settings.smtp_auth === 0
			|| props.settings.smtp_auth === '0'
		) ? false : true,
		username: props.settings.smtp_username,
		password: props.settings.smtp_password,
		secure: props.settings.smtp_secure,
		disableAutotls: (
			props.settings.smtp_disable_autotls === ''
			|| props.settings.smtp_disable_autotls === 0
			|| props.settings.smtp_disable_autotls === '0'
		) ? false : true,
	}

	const [formValues, setFormValues] = React.useState(savedState);

	const form = [
		{ id: 'host', label: __('SMTP server', 'kali-forms'), name: 'kaliforms_smtp_host', type: 'text', help: __('The server that will handle your email sending functionality.', 'kali-forms') },
		{ id: 'port', label: __('SMTP port', 'kali-forms'), name: 'kaliforms_smtp_port', type: 'number', help: __('The port that will be used to establish the connection to the server.', 'kali-forms'), help: __('The port that will be used to establish the connection to the server.', 'kali-forms') },
		{
			id: 'secure', label: __('Secure connection', 'kali-forms'), name: 'kaliforms_smtp_secure', type: 'select', choices: [
				{ value: 'None', label: __('No', 'kali-forms') },
				{ value: 'SSL', label: __('SSL', 'kali-forms') },
				{ value: 'TLS', label: __('TLS', 'kali-forms') },
				{ value: 'STARTTLS', label: __('STARTTLS', 'kali-forms') },
			], help: __('Set the encryption method used to secure your connection to the server.', 'kali-forms')
		},
		{ id: 'disableAutotls', label: __('Disable AutoTLS', 'kali-forms'), name: 'kaliforms_smtp_disable_autotls', type: 'checkbox', help: __('A TLS encryption is automatically used if the server supports it, which is recommended. If your server does not support this you can disable this default encryption from this option.', 'kali-forms') },
		{
			id: 'auth', label: __('Authenticate', 'kali-forms'), name: 'kaliforms_smtp_auth', type: 'checkbox', help: __('If the connection requires you to authenticate, this option will provide the fields required to add your login credentials or API Key.', 'kali-forms')
		},
		{ id: 'username', label: __('Username', 'kali-forms'), name: 'kaliforms_smtp_username', type: 'text', conditioned: { conditioner: 'auth', value: true }, help: __('The account used for the connection.', 'kali-forms') },
		{ id: 'password', label: __('Password', 'kali-forms'), name: 'kaliforms_smtp_password', type: 'password', conditioned: { conditioner: 'auth', value: true }, help: __('The password of your account.', 'kali-forms') },
	];

	const showItem = (el) => {
		let item = form.find(e => e.id === el);
		if (!item.hasOwnProperty('conditioned')) {
			return true;
		}

		return formValues[item.conditioned.conditioner] === item.conditioned.value
	}

	const inputChanged = (evt, id, type) => {
		let value = evt.target.value;
		if (type === 'checkbox') {
			value = evt.target.checked;
		}

		formValues[id] = value;
		setFormValues({ ...formValues });
	}

	const maybeProps = (item) => {
		let obj = {};
		if (item.type === 'checkbox') {
			obj.checked = formValues[item.id]
		}

		return obj;
	}

	return (
		<React.Fragment>
			<Typography variant={'h6'}>
				{__('Custom SMTP settings', 'kali-forms')}
			</Typography>
			<hr />
			<Grid container direction="row">
				<Grid item xs={9}>
					{form.map((e, idx) => {
						return (showItem(e.id) && <Grid key={e.name + idx} container direction="row" style={{ marginBottom: 8 }}>
							<Grid item xs={3}>
								<Typography variant="body1">
									<label htmlFor={e.id}>{e.label}</label>
								</Typography>
							</Grid>
							<Grid item xs={9}>
								<Choose>
									<When condition={e.type === 'checkbox'}>
										<Checkbox
											{...maybeProps(e)}
											name={e.name}
											type={e.type}
											value="on"
											id={e.id}
											onChange={evt => inputChanged(evt, e.id, e.type)} />
									</When>
									<When condition={e.type === 'select'}>
										<Select
											{...maybeProps(e)}
											name={e.name}
											value={formValues[e.id]}
											id={e.id}
											onChange={evt => inputChanged(evt, e.id, e.type)}
											input={<BootstrapInput />}
										>
											{e.choices.map(option => (<MenuItem value={option.value} key={option.value}>{option.label}</MenuItem>))}
										</Select>
									</When>
									<Otherwise>
										<BootstrapInput
											{...maybeProps(e)}
											id={e.id}
											name={e.name}
											type={e.type}
											value={formValues[e.id]}
											style={{ marginTop: e.type === 'checkbox' ? 5 : 0 }}
											onChange={evt => inputChanged(evt, e.id, e.type)}
											autoComplete={'off'}
										/>
									</Otherwise>
								</Choose>
								<If condition={e.hasOwnProperty('help') && e.help !== ''}>
									<Typography style={{ maxWidth: 400, marginBottom: 16, color: 'rgba(0, 0, 0, 0.54)' }} variant="body2">{e.help}</Typography>
								</If>
							</Grid>
						</Grid>)
					})}
				</Grid>
				<Grid item xs={3}>
					<MostUsedOptions formValues={formValues} setFormValues={setFormValues} />
				</Grid>
			</Grid>
		</React.Fragment>
	);
}
export default CustomSMTP;
