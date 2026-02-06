import React from 'react';
import Grid from '@material-ui/core/Grid';
import BootstrapInput from './../BootstrapInput';
import InputLabel from '@material-ui/core/InputLabel';
import FormControl from '@material-ui/core/FormControl';
import Select from '@material-ui/core/Select';
import MenuItem from '@material-ui/core/MenuItem';
import Button from './../Misc/MinimalButton';
import Icon from '@material-ui/core/Icon';
import WebHookEntityCreator from './WebHookEntityCreator';
import ConditionalEntity from './../Misc/ConditionalEntity';
import { observer } from "mobx-react";
import { store } from "./../../store/store";
const { __ } = wp.i18n;
const WebHookEditor = observer(props => {
	let currentHook = store._WEBHOOKS_.hooks[store._WEBHOOKS_.currentEditedHook];

	return (
		<React.Fragment>
			<Grid container direction="row" spacing={3}>
				<Grid item xs={12}>
					<FormControl>
						<InputLabel shrink>
							{__('Webhook name', 'kali-forms')}
						</InputLabel>
						<BootstrapInput
							value={currentHook.name || ''}
							onChange={e => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'name', e.target.value)}
							fullWidth={true}
						/>
					</FormControl>
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={3}>
				<Grid item xs={5}>
					<FormControl>
						<InputLabel shrink>
							{__('Payload URL', 'kali-forms')}
						</InputLabel>
						<BootstrapInput
							value={currentHook.url || ''}
							onChange={e => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'url', e.target.value)}
							fullWidth={true}
						/>
					</FormControl>
				</Grid>
				<Grid item xs={2}>
					<FormControl>
						<InputLabel shrink>
							{__('Method', 'kali-forms')}
						</InputLabel>
						<Select
							value={currentHook.method || 'POST'}
							multiple={false}
							onChange={e => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'method', e.target.value)}
							input={<BootstrapInput />}
						>
							<MenuItem value={'GET'}>
								{__('GET', 'kali-forms')}
							</MenuItem>
							<MenuItem value={'POST'}>
								{__('POST', 'kali-forms')}
							</MenuItem>
							<MenuItem value={'PUT'}>
								{__('PUT', 'kali-forms')}
							</MenuItem>
							<MenuItem value={'PATCH'}>
								{__('PATCH', 'kali-forms')}
							</MenuItem>
							<MenuItem value={'DELETE'}>
								{__('DELETE', 'kali-forms')}
							</MenuItem>
						</Select>
					</FormControl>
				</Grid>
				<Grid item xs={2}>
					<FormControl>
						<InputLabel shrink>
							{__('Format', 'kali-forms')}
						</InputLabel>
						<Select
							value={currentHook.format || 'json'}
							multiple={false}
							onChange={e => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'format', e.target.value)}
							input={<BootstrapInput />}
						>
							<MenuItem value={'json'}>
								{__('JSON', 'kali-forms')}
							</MenuItem>
							<MenuItem value={'form'}>
								{__('FORM', 'kali-forms')}
							</MenuItem>
						</Select>
					</FormControl>
				</Grid>
				<Grid item xs={3}>
					<FormControl>
						<InputLabel shrink>
							{__('Trigger event', 'kali-forms')}
						</InputLabel>
						<Select
							value={currentHook.event || 'afterFormProcess'}
							multiple={false}
							onChange={e => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'event', e.target.value)}
							input={<BootstrapInput />}
						>
							<MenuItem value={'beforeFormProcess'}>
								{__('Before form process', 'kali-forms')}
							</MenuItem>
							<MenuItem value={'afterFormProcess'}>
								{__('After form process', 'kali-forms')}
							</MenuItem>
						</Select>
					</FormControl>
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={3}>
				<Grid item xs={12}>
					<FormControl>
						<InputLabel shrink>
							{__('Authentication secret', 'kali-forms')}
						</InputLabel>
						<BootstrapInput
							value={currentHook.authentication || ''}
							onChange={e => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'authentication', e.target.value)}
							fullWidth={true}
						/>
					</FormControl>
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={3}>
				<Grid item xs={6}>
					<WebHookEntityCreator onChange={
						data => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'body', data)
					}
						entity={{ label: __('Request Body', 'kali-forms'), id: 'body' }} map={true} />
				</Grid>
				<Grid item xs={6}>
					<WebHookEntityCreator onChange={
						data => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'headers', data)
					}
						entity={{ label: __('Request Headers', 'kali-forms'), id: 'headers' }} />
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={3}>
				<Grid item xs={12}>
					<ConditionalEntity
						label={__('Should send webhook', 'kali-forms')}
						onChange={data => store._WEBHOOKS_.editHook(store._WEBHOOKS_.currentEditedHook, 'conditions', data)}
						changer={store._WEBHOOKS_.currentEditedHook}
						conditions={currentHook.conditions || []}
					/>
				</Grid>
			</Grid>
			<Grid container direction="row" spacing={3}>
				<Grid item xs={12}>
					<Button onClick={() => {
						props.setEditingHook(false)
						store._WEBHOOKS_.setEditedHook(false)
					}} style={{ paddingLeft: 16, paddingRight: 16 }}>
						<Icon className={'icon-back'} style={{ fontSize: 14, marginRight: 8 }} />
						{__('Back', 'kali-forms')}
					</Button>
				</Grid>
			</Grid>
		</React.Fragment>
	)
})

export default WebHookEditor;
