const { __ } = wp.i18n;
export default [
	{
		label: __('Sum of selected fields', 'kali-forms'),
		id: 'sum',
		args: -1,
		formula: '%s=sum(%s)',
	},
	{
		label: __('Subtract two fields', 'kali-forms'),
		id: 'subtract',
		args: 2,
		formula: '%s=subtract(%s)'
	},
	{
		label: __('Divide two fields', 'kali-forms'),
		id: 'divide',
		args: 2,
		formula: '%s=divide(%s)'
	},
	{
		label: __('Multiply selected fields', 'kali-forms'),
		id: 'multiply',
		args: -1,
		formula: '%s=multiply(%s)'
	},
	{
		label: __('Arithmetic average of fields', 'kali-forms'),
		id: 'arithmeticAverage',
		args: -1,
		formula: '%1s=sum(%3s)/%2s'
	},
	{
		label: __('BMI Formula (imperial)', 'kali-forms'),
		id: 'bmiImperial',
		args: 2,
		formula: '%1s=multiply(703,%2s)/pow(%3s, 2)',
		argsHelper: [
			__('Weight', 'kali-forms'),
			__('Height', 'kali-forms'),
		],
	},
	{
		label: __('BMI Formula (metric)', 'kali-forms'),
		id: 'bmiMetric',
		args: 2,
		formula: '%1s=%2s/pow(%3s/100, 2)',
		argsHelper: [
			__('Weight', 'kali-forms'),
			__('Height', 'kali-forms'),
		],
	},
	{
		label: __('Minutes to seconds', 'kali-forms'),
		id: 'minutesToSeconds',
		args: 1,
		formula: '%1s=%2s minute to seconds',
	},
	{
		label: __('Seconds to minutes', 'kali-forms'),
		id: 'secondsToMinute',
		args: 1,
		formula: '%1s=%2s seconds to minute',
	},
	{
		label: __('Seconds to hours', 'kali-forms'),
		id: 'secondsToHour',
		args: 1,
		formula: '%1s=%2s seconds to hour',
	},
	{
		label: __('Minutes to hours', 'kali-forms'),
		id: 'minutesToHours',
		args: 1,
		formula: '%1s=%2s minutes to hour',
	},
	{
		label: __('Hour:Minute:Seconds to seconds', 'kali-forms'),
		id: 'hourMinuteSeconds',
		args: 3,
		formula: '%1s=sum(%2s hour to seconds, %3s minute to seconds, %4s second to second) to seconds',
		argsHelper: [
			__('Hours', 'kali-forms'),
			__('Minutes', 'kali-forms'),
			__('Seconds', 'kali-forms'),
		],
	},
	{
		label: __('Speed', 'kali-forms'),
		id: 'speed',
		args: 2,
		formula: '%1s=%2s km/%3s hour',
		argsHelper: [
			__('Distance', 'kali-forms'),
			__('Time', 'kali-forms')
		],
	},
	{
		label: __('Running pace', 'kali-forms'),
		id: 'pace',
		args: 4,
		formula: '%1s=minuteConverter(sum(%2s hour to seconds, %3s minutes to seconds, %4s seconds to seconds)/%5s to minutes)',
		argsHelper: [
			__('Hours', 'kali-forms'),
			__('Minutes', 'kali-forms'),
			__('Seconds', 'kali-forms'),
			__('Distance', 'kali-forms')
		],
	},
	{
		label: __('Running time', 'kali-forms'),
		id: 'runningTime',
		args: 3,
		formula: '%1s=hourMinuteConverter(multiply(%2s, sum(%3s minutes to seconds, %4s seconds to seconds)) to minutes)',
		argsHelper: [
			__('Distance', 'kali-forms'),
			__('Minutes', 'kali-forms'),
			__('Seconds', 'kali-forms')
		],
	},
	{
		label: __('Running distance', 'kali-forms'),
		id: 'runningDistance',
		args: 5,
		formula: '%1s=sum(%2s hour to seconds, %3s minutes to seconds, %4s seconds to seconds)/sum(%5s minutes to seconds, %6s seconds to seconds)',
		argsHelper: [
			__('Hours', 'kali-forms'),
			__('Minutes', 'kali-forms'),
			__('Seconds', 'kali-forms'),
			__('Minutes', 'kali-forms'),
			__('Seconds', 'kali-forms')
		],
	},
	{
		label: __('Day difference between dates', 'kali-forms'),
		id: 'dayDifference',
		args: 2,
		formula: '%1s=differenceInDays(%2s, %3s)',
		argsHelper: [
			__('Start date', 'kali-forms'),
			__('End date', 'kali-forms')
		],
	}
]
