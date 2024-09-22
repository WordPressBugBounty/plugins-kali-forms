import React, { useState, useContext } from 'react'
import { FormEntriesContext } from './../Context/FormEntriesContext';
import { UiContext } from './../Context/UiContext';
import { Table, Button, Checkbox } from 'antd';
import { useParams, Link } from "react-router-dom";
import Modal from 'antd/lib/modal/Modal';
import useLocalStorage from '../utils/useLocalStorage';
const { __, sprintf } = wp.i18n;

export default function FormEntries() {
	const context = useContext(FormEntriesContext)
	const { id } = useParams();
	const [data] = context.data;
	const [columns] = context.columns;
	const [loading] = context.loading;
	const [tableWidth] = context.tableWidth;
	const [selectedFilter] = context.selectedFilter;
	const [pagination, setPagination] = context.pagination;
	const [selectedRowKeys, setSelectedRowKeys] = useState([]);
	const [ui, setUi] = useContext(UiContext);
	const hasSelected = selectedRowKeys.length > 0;
	const [modalShown, setModalShown] = useState(false);
	const [hiddenColumns, setHiddenColumns] = useLocalStorage(`hiddenColumns${id}`, []);
	const onSelectChange = selectedKeys => {
		setSelectedRowKeys(selectedKeys);
	}

	const pageChange = (page, pageSize) => {
		context.fetchData({ page, pageSize, filter: selectedFilter })
	}

	const updatePagination = (current, size) => {
		setPagination(prevPag => { return { ...prevPag, pageSize: size } })
	}

	const deleteEntries = () => {
		context.deleteEntries(selectedRowKeys);
		setSelectedRowKeys([]);
	}

	const updateUi = () => {
		setUi(prevUi => { return { ...prevUi, selectedNavbar: ['forms'] } });
	}

	const onClickColumnSelector = () => {
		setModalShown(true);
	};

	const changedCheckbox = (key) => {
		setHiddenColumns(prevHiddenColumns => {
			if (prevHiddenColumns.includes(key)) {
				return prevHiddenColumns.filter(item => item !== key);
			} else {
				return [...prevHiddenColumns, key];
			}
		});
	};

	const columnsToShow = columns.filter(column => !hiddenColumns.includes(column.key));

	return (
		<React.Fragment>
			<If condition={id}>
				<div style={{ marginBottom: 16 }}>
					<Button type="primary" onClick={deleteEntries} disabled={!hasSelected} loading={loading}>
						{__('Delete', 'kaliforms')}
					</Button>
					<span style={{ marginLeft: 8 }}>
						{hasSelected ? sprintf(__('Selected %s items', 'kaliforms'), selectedRowKeys.length) : ''}
					</span>
					<Button type="primary" onClick={onClickColumnSelector} style={{ marginLeft: 20 }}>
						{__('Column selector', 'kaliforms')}
					</Button>
				</div>

				<Table sticky
					scroll={{ x: columnsToShow.length * 150 }}
					loading={loading}
					columns={columnsToShow}
					dataSource={data}
					bordered={true}
					rowSelection={data.length ? {
						selectedRowKeys,
						onChange: onSelectChange,
					} : false}
					pagination={{
						onChange: pageChange,
						current: pagination.currentPage,
						total: pagination.totalRows,
						showSizeChanger: true,
						onShowSizeChange: updatePagination,
						pageSize: pagination.pageSize,
						pageSizeOptions: [10, 15, 25],
					}}
				/>
			</If>
			<If condition={!id}>
				<Link to={'/'} onClick={updateUi}>{__('... select a form first!', 'kaliforms')}</Link>
			</If>
			<Modal visible={modalShown} onCancel={() => setModalShown(false)} onOk={() => setModalShown(false)}>
				{columns.map((column) =>
					<div key={column.key}><Checkbox value={column.key} checked={!hiddenColumns.includes(column.key)} onChange={() => changedCheckbox(column.key)} /> {column.title}</div>
				)}
			</Modal>
		</React.Fragment>
	)
}
