<?php

namespace App\Http\Requests\Vendor;

use App\Enums\VendorType;
use Illuminate\Validation\Rule;

class UpdateVendorRequest extends StoreVendorRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'classification' => ['sometimes', 'required', Rule::enum(VendorType::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        // status tidak dipaksa ACTIVE saat update
    }

    public function validatedWithFlags(): array
    {
        $data = $this->validated();

        if (isset($data['classification'])) {
            $data['is_lpp'] = VendorType::from($data['classification'])->isLpp();
        }

        return $data;
    }
}
