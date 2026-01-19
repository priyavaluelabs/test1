public $formData = [
    'name' => null,
    'products' => null,
    'discount_type' => 'percentage', // default value
    'value' => null,
    'description' => null,
];



 ->statePath('formData.value')


 $data = $this->formData;
