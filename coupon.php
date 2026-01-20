// Only if specific products selected
if (
    ! empty($data['products']) &&
    ! in_array('all', $data['products'])
) {
    $couponData['applies_to'] = [
        'products' => $data['products'],
    ];
}

// Always store in metadata (string only)
$couponData['metadata'] = [
    'description' => $data['description'] ?? '',
    'applies_products' => implode(',', $data['products'] ?? ['all']),
];
