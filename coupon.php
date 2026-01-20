if (in_array('all', $data['products'] ?? [])) {
    $products = 'all';
} else {
    $products = implode(',', $data['products']);
}
