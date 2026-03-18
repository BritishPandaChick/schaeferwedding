import { __ } from '@wordpress/i18n';
import {
	Box,
	Flex,
	Text,
	Button,
	Textarea,
	HStack,
	Input,
} from '@chakra-ui/react';

/**
 * SettingToggle Component
 * Toggle button for on/off settings
 */
export function SettingToggle( {
	label,
	description,
	enabled,
	onChange,
	disabled = false,
	recommended = false,
	showProBadge = true,
} ) {
	return (
		<Flex
			align="flex-start"
			justify="space-between"
			gap={ 6 }
			py={ 5 }
			opacity={ disabled ? 0.5 : 1 }
		>
			<Box flex="1">
				<Flex align="center" gap={ 2 } mb={ 2 }>
					<Text
						as="label"
						fontWeight="600"
						color="gray.800"
						fontSize="15px"
					>
						{ label }
					</Text>
					{ recommended && (
						<Box
							as="span"
							bg="green.100"
							color="green.700"
							fontSize="10px"
							fontWeight="700"
							px={ 2 }
							py={ 0.5 }
							borderRadius="full"
							textTransform="uppercase"
						>
							{ __( 'Recommended', 'anti-spam' ) }
						</Box>
					) }
					{ disabled && showProBadge && (
						<Box
							as="span"
							bg="purple.100"
							color="purple.700"
							fontSize="10px"
							fontWeight="700"
							px={ 2 }
							py={ 0.5 }
							borderRadius="full"
							textTransform="uppercase"
						>
							{ __( 'Pro', 'anti-spam' ) }
						</Box>
					) }
				</Flex>
				<Text fontSize="sm" color="gray.600" lineHeight="1.6">
					{ description }
				</Text>
			</Box>
			<Flex
				gap={ 0 }
				bg="gray.100"
				borderRadius="lg"
				p={ 1 }
				flexShrink={ 0 }
				cursor={ disabled ? 'not-allowed' : 'pointer' }
			>
				<Button
					onClick={ () => ! disabled && onChange( true ) }
					px={ 5 }
					py={ 2 }
					borderRadius="md"
					fontSize="sm"
					fontWeight="600"
					transition="all 0.2s"
					bg={ enabled ? 'white' : 'transparent' }
					color={ enabled ? 'gray.900' : 'gray.600' }
					border="none"
					boxShadow={ enabled ? 'sm' : 'none' }
					_hover={ {
						bg: disabled
							? 'transparent'
							: enabled
							? 'white'
							: 'gray.50',
					} }
					minW="60px"
					cursor={ disabled ? 'not-allowed' : 'pointer' }
					isDisabled={ disabled }
				>
					{ __( 'On', 'anti-spam' ) }
				</Button>
				<Button
					onClick={ () => ! disabled && onChange( false ) }
					px={ 5 }
					py={ 2 }
					borderRadius="md"
					fontSize="sm"
					fontWeight="600"
					transition="all 0.2s"
					bg={ ! enabled ? 'white' : 'transparent' }
					color={ ! enabled ? 'gray.900' : 'gray.600' }
					border="none"
					boxShadow={ ! enabled ? 'sm' : 'none' }
					_hover={ {
						bg: disabled
							? 'transparent'
							: ! enabled
							? 'white'
							: 'gray.50',
					} }
					minW="60px"
					cursor={ disabled ? 'not-allowed' : 'pointer' }
					isDisabled={ disabled }
				>
					{ __( 'Off', 'anti-spam' ) }
				</Button>
			</Flex>
		</Flex>
	);
}

/**
 * DropdownControl Component
 * Dropdown select control for choosing from predefined options
 */
export function DropdownControl( {
	label,
	description,
	value,
	onChange,
	options,
	disabled = false,
} ) {
	return (
		<Flex
			align="flex-start"
			justify="space-between"
			direction={ { base: 'column', md: 'row' } }
			gap={ { base: 3, md: 6 } }
			py={ 5 }
			opacity={ disabled ? 0.5 : 1 }
		>
			<Box flex="1">
				<Text
					as="label"
					fontWeight="600"
					color="gray.800"
					fontSize="15px"
					mb={ 2 }
					display="block"
				>
					{ label }
				</Text>
				<Text fontSize="sm" color="gray.600" lineHeight="1.6">
					{ description }
				</Text>
			</Box>
			<Box
				flexShrink={ 0 }
				minW={ { base: 'auto', md: '200px' } }
				w={ { base: '100%', md: 'auto' } }
			>
				<select
					value={ value }
					onChange={ ( e ) => onChange( e.target.value ) }
					disabled={ disabled }
					style={ {
						width: '100%',
						padding: '8px 12px',
						borderRadius: '8px',
						border: '1px solid #e2e8f0',
						fontSize: '14px',
						backgroundColor: disabled ? '#f7fafc' : 'white',
						cursor: disabled ? 'not-allowed' : 'pointer',
					} }
				>
					{ options.map( ( option ) => (
						<option key={ option[ 0 ] } value={ option[ 0 ] }>
							{ option[ 1 ] }
						</option>
					) ) }
				</select>
			</Box>
		</Flex>
	);
}

/**
 * TextareaControl Component
 * Multi-line text input control
 */
export function TextareaControl( {
	label,
	description,
	value,
	onChange,
	disabled = false,
	rows = 5,
	placeholder,
} ) {
	return (
		<Flex
			align="flex-start"
			justify="space-between"
			gap={ 6 }
			py={ 5 }
			opacity={ disabled ? 0.5 : 1 }
		>
			<Box flex="1">
				<Text
					as="label"
					fontWeight="600"
					color="gray.800"
					fontSize="15px"
					mb={ 2 }
					display="block"
				>
					{ label }
				</Text>
				<Text fontSize="sm" color="gray.600" lineHeight="1.6" mb={ 3 }>
					{ description }
				</Text>
				<Textarea
					value={ value }
					onChange={ ( e ) => onChange( e.target.value ) }
					disabled={ disabled }
					rows={ rows }
					fontSize="sm"
					borderRadius="lg"
					placeholder={ placeholder }
				/>
			</Box>
		</Flex>
	);
}

/**
 * SegmentedButton Component
 * Multi-option button group matching SettingToggle's visual style
 */
export function SegmentedButton( {
	options,
	value,
	onChange,
	disabled = false,
} ) {
	return (
		<HStack
			gap={ 0 }
			bg="gray.100"
			borderRadius="lg"
			p={ 1 }
			flexShrink={ 0 }
			display="inline-flex"
			flexWrap="wrap"
			cursor={ disabled ? 'not-allowed' : 'pointer' }
		>
			{ options.map( ( option ) => (
				<Button
					key={ option.value }
					onClick={ () => ! disabled && onChange( option.value ) }
					px={ 4 }
					py={ 2 }
					borderRadius="md"
					fontSize="sm"
					fontWeight="600"
					transition="all 0.2s"
					bg={ value === option.value ? 'white' : 'transparent' }
					color={ value === option.value ? 'gray.900' : 'gray.600' }
					border="none"
					boxShadow={ value === option.value ? 'sm' : 'none' }
					_hover={ {
						bg: disabled
							? 'transparent'
							: value === option.value
							? 'white'
							: 'gray.50',
					} }
					minW="auto"
					cursor={ disabled ? 'not-allowed' : 'pointer' }
					isDisabled={ disabled }
				>
					{ option.label }
				</Button>
			) ) }
		</HStack>
	);
}

/**
 * TextboxControl Component
 * Single-line text/number input control
 */
export function TextboxControl( {
	label,
	description,
	value,
	onChange,
	disabled = false,
	placeholder,
	type = 'text',
	...props
} ) {
	return (
		<Flex
			align="flex-start"
			justify="space-between"
			direction={ { base: 'column', md: 'row' } }
			gap={ { base: 3, md: 6 } }
			py={ 5 }
			opacity={ disabled ? 0.5 : 1 }
		>
			<Box flex="1">
				<Text
					as="label"
					fontWeight="600"
					color="gray.800"
					fontSize="15px"
					mb={ 2 }
					display="block"
				>
					{ label }
				</Text>
				<Text fontSize="sm" color="gray.600" lineHeight="1.6">
					{ description }
				</Text>
			</Box>
			<Box
				flexShrink={ 0 }
				minW={ { base: 'auto', md: '200px' } }
				w={ { base: '100%', md: 'auto' } }
			>
				<Input
					type={ type }
					value={ value }
					onChange={ ( e ) =>
						onChange(
							type === 'number'
								? parseInt( e.target.value, 10 ) || 0
								: e.target.value
						)
					}
					disabled={ disabled }
					fontSize="sm"
					borderRadius="lg"
					placeholder={ placeholder }
					{ ...props }
				/>
			</Box>
		</Flex>
	);
}
