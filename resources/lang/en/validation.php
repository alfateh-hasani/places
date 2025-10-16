<?php
return [
    'custom' => [
        'checkin' => [
            'required' => 'Check-in date is required.',
            'date' => 'Invalid date format.',
            'after_or_equal' => 'Check-in date must be today or later.',
        ],
        'checkout' => [
            'required' => 'Check-out date is required.',
            'date' => 'Invalid date format.',
            'after' => 'Check-out date must be after the check-in date.',
        ],
        'number_of_adults' => [
            'required' => 'Number of adults is required.',
            'integer' => 'Number of adults must be an integer.',
            'min' => 'At least one adult is required.',
            'max' => 'Number of adults cannot exceed 10.',
        ],
        'number_of_children' => [
            'required' => 'Number of children is required.',
            'integer' => 'Number of children must be an integer.',
            'min' => 'Number of children cannot be negative.',
            'max' => 'Number of children cannot exceed 10.',
        ],
        'coupon_code' => [
            'string' => 'Invalid coupon format.',
        ],
    ],
];
