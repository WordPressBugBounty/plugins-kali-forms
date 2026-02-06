import React, { useContext } from 'react'
import { Table, Space, PageHeader } from 'antd';
import { Link } from 'react-router-dom';
import { UiContext } from './../Context/UiContext';
import { AppPropsContext } from './../Context/AppPropsContext';
import { __ } from '@wordpress/i18n';
export default function Forms() {
	const context = useContext(AppPropsContext);
	const [ui, setUi] = useContext(UiContext);

	const updateUi = () => {
		setUi(prevUi => { return { ...prevUi, selectedNavbar: ['form-entries'] } });
	}
	const columns = [
		{
			title: __('Id', 'kali-forms'),
			dataIndex: 'id',
			key: 'id'
		},
		{
			title: __('Form name', 'kali-forms'),
			dataIndex: 'name',
			key: 'name'
		},
		{
			title: __('Entries', 'kali-forms'),
			dataIndex: 'entries',
			key: 'entries'
		},
		{
			title: __('Actions', 'kali-forms'),
			dataIndex: 'actions',
			key: 'actions',
			render: (text, record) => (
				<Space size="middle">
					<Link to={`/form-entries/${record.id}`} onClick={updateUi}>{__('View entries', 'kali-forms')}</Link>
				</Space>
			),

		}
	];
	return (
		<React.Fragment>
			<PageHeader
				backIcon={false}
				title={__('Forms', 'kali-forms')}
				subTitle={__('All your existing forms', 'kali-forms')}
			/>
			<Table bordered={true} columns={columns} dataSource={context.allForms} pagination={false} />
		</React.Fragment>
	)
}
