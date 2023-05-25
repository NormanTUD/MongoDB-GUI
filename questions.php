<?php
$questions = [
	[
		'group' => 'personal_information',
		'questions' => [
			[
				'question' => 'name_question',
				'input_type' => 'text',
				'required' => true,
				'name' => 'your_name'
			],
			[
				'question' => 'age_question',
				'input_type' => 'number',
				'required' => true,
				'name' => 'age'
			],
			[
				'question' => 'gender_question',
				'input_type' => 'radio',
				'name' => 'gender',
				'options' => [
					'male_option',
					'female_option',
					'other_option'
				]
			]
		]
	],
	[
		'group' => 'hobbies_question',
		'questions' => [
			[
				'question' => 'hobbies_question',
				'input_type' => 'checkbox',
				'name' => 'hobbies',
				'options' => [
					'reading_option',
					'sports_option',
					'music_option',
					'traveling_option'
				]
			]
		]
	],
	[
		'group' => 'location_question',
		'questions' => [
			[
				'question' => 'address_question',
				'input_type' => 'text',
				'name' => 'location',
				'fields' => [
					[
						'label' => 'street_label',
						'required' => true,
						'name' => 'street'
					],
					[
						'label' => 'city_label',
						'required' => true,
						'name' => 'city'
					],
					[
						'label' => 'state_label',
						'required' => true,
						'name' => 'state'
					],
					[
						'label' => 'country_label',
						'required' => true,
						'name' => 'country'
					]
				]
			],
			[
				'question' => 'country_residence_question',
				'input_type' => 'select',
				'name' => 'country',
				'options' => [
					'USA',
					'Canada',
					'UK',
					'Australia',
					'other_option'
				]
			]
		]
	]
];
?>
