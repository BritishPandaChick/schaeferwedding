import { useState, useEffect } from '@wordpress/element';
import { Box, Text, Button, VStack } from '@chakra-ui/react';

/**
 * NavItem Component
 * Sidebar navigation item with icon and optional submenus
 */
function NavItem( { icon, label, active, onClick, subItems } ) {
	const [ isExpanded, setIsExpanded ] = useState( active || false );

	useEffect( () => {
		if ( active && subItems && subItems.length > 0 ) {
			setIsExpanded( true );
		}
	}, [ active, subItems ] );

	const icons = {
		activity: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<polyline
					points="22 12 18 12 15 21 9 3 6 12 2 12"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		shield: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path
					d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		lock: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<rect
					x="3"
					y="11"
					width="18"
					height="11"
					rx="2"
					ry="2"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<path
					d="M7 11V7a5 5 0 0 1 10 0v4"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		database: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<ellipse
					cx="12"
					cy="5"
					rx="9"
					ry="3"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<path
					d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		settings: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<circle
					cx="12"
					cy="12"
					r="3"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<path
					d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m4.24 4.24l4.24 4.24M1 12h6m6 0h6M5.64 18.36l4.24-4.24m4.24-4.24l4.24-4.24"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		list: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<line
					x1="8"
					y1="6"
					x2="21"
					y2="6"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="8"
					y1="12"
					x2="21"
					y2="12"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="8"
					y1="18"
					x2="21"
					y2="18"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="3"
					y1="6"
					x2="3.01"
					y2="6"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="3"
					y1="12"
					x2="3.01"
					y2="12"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="3"
					y1="18"
					x2="3.01"
					y2="18"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		key: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path
					d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		alert: (
			<svg
				width="20"
				height="20"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path
					d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="12"
					y1="9"
					x2="12"
					y2="13"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
				<line
					x1="12"
					y1="17"
					x2="12.01"
					y2="17"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
		chevronDown: (
			<svg
				width="16"
				height="16"
				viewBox="0 0 24 24"
				fill="none"
				xmlns="http://www.w3.org/2000/svg"
			>
				<polyline
					points="6 9 12 15 18 9"
					stroke="currentColor"
					strokeWidth="2"
					strokeLinecap="round"
					strokeLinejoin="round"
				/>
			</svg>
		),
	};

	const handleClick = () => {
		if ( subItems && subItems.length > 0 ) {
			setIsExpanded( ! isExpanded );
		} else if ( onClick ) {
			onClick();
		}
	};

	return (
		<Box>
			<Button
				onClick={ handleClick }
				w="full"
				display="flex"
				align="center"
				gap={ 3 }
				px={ 3 }
				py={ 2 }
				borderRadius="lg"
				fontSize="sm"
				fontWeight="medium"
				transition="all 0.2s"
				position="relative"
				justifyContent="flex-start"
				bg={ active && ! subItems ? 'gray.100' : 'transparent' }
				color={ active && ! subItems ? 'gray.900' : 'gray.700' }
				_hover={ {
					bg: active && ! subItems ? 'gray.100' : 'gray.50',
				} }
			>
				{ active && ! subItems && (
					<Box
						position="absolute"
						left="0"
						top="4px"
						bottom="4px"
						w="2px"
						bg="purple.500"
						borderRadius="0 2px 2px 0"
					/>
				) }
				{ icon && (
					<Box as="span" display="inline-flex">
						{ icons[ icon ] }
					</Box>
				) }
				<Text flex="1" textAlign="left">
					{ label }
				</Text>
				{ subItems && subItems.length > 0 && (
					<Box
						as="span"
						display="inline-flex"
						transform={
							isExpanded ? 'rotate(0deg)' : 'rotate(-90deg)'
						}
						transition="transform 0.2s"
					>
						{ icons.chevronDown }
					</Box>
				) }
			</Button>

			{ subItems && subItems.length > 0 && isExpanded && (
				<VStack gap={ 1 } align="stretch" mt={ 1 } ml={ 3 }>
					{ subItems.map( ( subItem, index ) => (
						<Button
							key={ index }
							onClick={ subItem.onClick }
							w="full"
							display="flex"
							align="center"
							gap={ 3 }
							px={ 3 }
							py={ 2 }
							borderRadius="lg"
							fontSize="sm"
							fontWeight="normal"
							transition="all 0.2s"
							position="relative"
							justifyContent="flex-start"
							bg={ subItem.active ? 'gray.100' : 'transparent' }
							color={ subItem.active ? 'gray.900' : 'gray.600' }
							_hover={ {
								bg: subItem.active ? 'gray.100' : 'gray.50',
							} }
						>
							{ subItem.active && (
								<Box
									position="absolute"
									left="0"
									top="4px"
									bottom="4px"
									w="2px"
									bg="purple.500"
									borderRadius="0 2px 2px 0"
								/>
							) }
							<Text flex="1" textAlign="left">
								{ subItem.label }
							</Text>
						</Button>
					) ) }
				</VStack>
			) }
		</Box>
	);
}

export default NavItem;
