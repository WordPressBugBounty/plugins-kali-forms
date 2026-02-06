import React, { useMemo } from 'react'
import { resendNotifications } from './../utils/ResendNotifications';
import { getFrontendLink } from './../utils/GetFrontendLink';

import {
	Link
} from "react-router-dom";

import { Space, Button, Tooltip } from 'antd';

import EditOutlined from '@ant-design/icons/EditOutlined'
import SendOutlined from '@ant-design/icons/SendOutlined';
import LinkOutlined from '@ant-design/icons/LinkOutlined';

import { __ } from '@wordpress/i18n';

export default function ActionColumn({ data, record, submissionPage }) {

	return (
		<Space size={6}>
			<Tooltip title={__('Edit entry', 'kali-forms')}>
				<Link to={`/form-entry/${record.key}`}><Button shape="circle" icon={<EditOutlined />}></Button></Link>
			</Tooltip>
			<Tooltip title={__('Resend notifications', 'kali-forms')}>
				<Button shape="circle" onClick={() => resendNotifications(record.key, data[0].formId)} icon=
					{<SendOutlined />}></Button>
			</Tooltip>
			<If condition={submissionPage}>
				<Tooltip title={__('Grab the submission link', 'kali-forms')}>
					<Button shape="circle" onClick={() => getFrontendLink(record.key, data[0].formId)} icon={<LinkOutlined />} />
				</Tooltip>
			</If>
		</Space >
	)
}
