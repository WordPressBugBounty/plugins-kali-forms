import React from 'react';

import RenderEmpty from './../Misc/RenderEmpty';
import { Image, Carousel, Tag, Button } from 'antd';

import { createLinkMap } from './GetFrontendLink';
import { calculateColumnsWidth } from './DynamicColumnsHelper';
import ActionColumn from '../Misc/ActionColumn';

import { __ } from '@wordpress/i18n';
const RESERVED_NAMES = ['children'];

const DataFormatter = (data) => {
	const submissionLinkMap = createLinkMap();
	const createColumns = (data) => {
		let columns = [];
		let filters = [];
		if (data.length > 0) {
			data[0].fields.map(el => {
				if (['divider', 'freeText', 'grecaptcha', 'submitButton', 'button', 'smartTextOutput', 'pageBreak', 'stripe', 'paypal'].includes(el.type)) {
					return;
				}

				columns.push({
					title: el.caption,
					key: el.id,
					dataIndex: RESERVED_NAMES.includes(el.id) ? el.id + '[no-conflict]' : el.id,
				})

				if (['digitalSignature'].includes(el.type)) {
					return;
				}

				let filter = {
					title: el.caption,
					key: el.id,
					dataIndex: RESERVED_NAMES.includes(el.id) ? el.id + '[no-conflict]' : el.id,
					type: el.type,
					initialValue: '',
				}

				if (el?.props?.choices || el?.props?.products) {
					filter['choices'] = el.props?.products ? el.props.products : el.props.choices
					filter.initialValue = []
				}

				if (el?.props?.min || el?.props?.max) {
					filter['minMax'] = {
						min: el?.props?.min ? el.props.min : 0,
						max: el?.props?.max ? el.props.max : 5
					}

					filter.initialValue = 0
				}

				filters.push(filter);
			})
		}
		columns.push({
			title: __('Actions', 'kali-forms'),
			key: 'actions',
			dataIndex: 'actions',
			fixed: 'right',
			width: submissionLinkMap[data[0].formId] ? 150 : 100,
			render: (text, record) => <ActionColumn data={data} record={record} submissionPage={submissionLinkMap[data[0].formId]} />
		})
		return { columns, filters };
	}

	const getValue = (type, value) => {
		let saneValue = '';
		const isImageUrl = url => typeof url === 'string' && /\.(png|jpe?g|gif|bmp|webp|svg)$/i.test(url);
		const openUrl = url => window.open(url, '_blank');

		switch (type) {
			case 'imageRadio':
				saneValue = value ? (<Image width={100} src={value.img} />) : ''
				break
			case 'digitalSignature':
				saneValue = value ? (<Image width={100} src={value} />) : ''
				break;
			case 'fileUpload':
				{
					const urls = value?.urls || [];
					const combined = value?.combined || [];
					const allImages = urls.length > 0 && urls.every(isImageUrl);
					console.log(urls);
					if (allImages) {
						saneValue = (
							<Carousel>
								{urls.map((url, idx) => <Image key={'key-' + idx} width={125} src={url} />)}
							</Carousel>
						)
					} else {
						const listItems = (combined.length ? combined : urls).map((el, idx) => {
							const url = typeof el === 'string' ? el : el.url;
							const title = typeof el === 'string' ? __('Download file', 'kali-forms') : el.title;

							return url !== ''
								? <li key={'uploaded-' + (el.id || idx)}><Button onClick={() => openUrl(url)}>{title}</Button></li>
								: <li key={'uploaded-' + idx}>{__('No file uploaded', 'kali-forms')}</li>
						});

						saneValue = (
							<React.Fragment>
								<ul>
									{listItems}
								</ul>
							</React.Fragment>
						)
					}
				}
				break;
			case 'colorPicker':
				saneValue = (
					<Tag color={value}>{value}</Tag>
				)
				break;
			case 'product':
				saneValue = value.label + ' ' + value.price.toFixed(2)
				break;
			default:
				saneValue = value;
				break;
		}

		return saneValue ? saneValue : (<RenderEmpty />);
	}
	const createRows = (data) => {
		let rows = [];
		data.map(row => {
			let obj = {
				key: row.id,
			};
			row.fields.map(property => {
				let id = RESERVED_NAMES.includes(property.id) ? property.id + '[no-conflict]' : property.id
				obj[id] = getValue(property.type, property.value)
			})

			rows.push(obj);
		})
		return rows;
	}

	const { columns, filters } = createColumns(data);
	const rows = createRows(data);

	const dataTable = calculateColumnsWidth(columns, rows, 500);
	const { tableWidth } = dataTable;

	return { columns, rows, tableWidth, filters }
}

export default DataFormatter;
